<?php

declare(strict_types=1);

namespace T3Docs\GuidesExtension\Renderer\Parallel;

use League\Tactician\CommandBus;
use phpDocumentor\Guides\Handlers\RenderCommand;
use phpDocumentor\Guides\Handlers\RenderDocumentCommand;
use phpDocumentor\Guides\Nodes\DocumentNode;
use phpDocumentor\Guides\RenderContext;
use phpDocumentor\Guides\Renderer\TypeRenderer;
use Psr\Log\LoggerInterface;

/**
 * Parallel renderer using pcntl_fork for CPU-bound Twig rendering.
 *
 * This renderer parallelizes the render phase across multiple forked processes.
 * Key design decisions:
 * 1. Fork AFTER parsing - AST is in memory, inherited via copy-on-write
 * 2. Each child renders to different output files (no write conflicts)
 * 3. Falls back to sequential rendering when pcntl unavailable or document count is low
 * 4. Uses DocumentNavigationProvider to maintain correct prev/next navigation
 *
 * The prev/next navigation challenge: when rendering a batch of documents [5,6,7]
 * in a child process, we need to know that doc 5's "previous" is doc 4 (not in our batch).
 * This is solved by initializing DocumentNavigationProvider with the full document order
 * before forking. The provider state is inherited via copy-on-write and TwigExtension
 * uses it for prev/next lookups instead of the iterator.
 *
 * @see https://www.php.net/manual/en/function.pcntl-fork.php
 */
final class ForkingRenderer implements TypeRenderer
{
    /** Minimum document count before parallelization is worthwhile */
    private const int MIN_DOCS_FOR_PARALLEL = 10;

    private int $workerCount;
    private bool $parallelEnabled = true;

    /** @var array<int, int> PIDs of child processes */
    private array $childPids = [];

    private int $totalRendered = 0;

    public function __construct(
        private readonly CommandBus $commandBus,
        private readonly DocumentNavigationProvider $navigationProvider,
        private readonly ?LoggerInterface $logger = null,
        ?int $workerCount = null,
    ) {
        // Default to number of CPU cores, with reasonable bounds
        $this->workerCount = $workerCount ?? $this->detectCpuCount();
    }

    public function render(RenderCommand $renderCommand): void
    {
        $documentCount = count($renderCommand->getDocumentArray());

        // Check if parallel rendering is beneficial and available
        if (!$this->shouldFork($documentCount)) {
            $this->logger?->debug(sprintf(
                'Using sequential rendering: %d documents (parallel=%s, pcntl=%s, workers=%d)',
                $documentCount,
                $this->parallelEnabled ? 'enabled' : 'disabled',
                function_exists('pcntl_fork') ? 'available' : 'unavailable',
                $this->workerCount
            ));

            $this->renderSequentially($renderCommand);
            return;
        }

        // For parallel rendering, we need to convert the iterator to an array
        // The iterator must be rewound first as it may have been partially consumed
        $iterator = $renderCommand->getDocumentIterator();
        $iterator->rewind();
        $orderedDocuments = iterator_to_array($iterator, false);

        $this->logger?->info(sprintf(
            'Starting parallel rendering: %d documents across %d workers',
            $documentCount,
            $this->workerCount
        ));

        // NOTE: We intentionally do NOT pre-warm Twig cache here because:
        // 1. Loading templates initializes Twig's runtime, locking it
        // 2. After locking, addGlobal() calls fail in child processes
        // 3. Twig's file cache uses atomic writes, so race conditions are unlikely
        // If race conditions prove problematic, we could use per-worker cache directories

        // Initialize navigation provider with full document order BEFORE forking.
        // This allows all child processes to have correct prev/next navigation,
        // as the provider state is inherited via copy-on-write.
        $this->navigationProvider->initializeFromArray($orderedDocuments);

        // Partition documents into batches
        $batches = $this->partitionDocuments($orderedDocuments, $this->workerCount);

        // Fork children
        $this->childPids = [];
        foreach ($batches as $workerId => $batch) {
            if ($batch === []) {
                continue;
            }

            $pid = pcntl_fork();

            if ($pid === -1) {
                // Fork failed - fall back to sequential
                $this->logger?->error('pcntl_fork failed, falling back to sequential rendering');
                // Iterator was exhausted, render from array instead
                $this->renderFromArray($renderCommand, $orderedDocuments);
                return;
            }

            if ($pid === 0) {
                // Child process: render batch
                // Navigation provider is already initialized (inherited via COW)
                $this->renderChildBatch($batch, $renderCommand, $workerId);
                exit(0);
            }

            // Parent: record child PID
            $this->childPids[$workerId] = $pid;
            $this->logger?->debug(sprintf(
                'Forked worker %d (PID %d): %d documents',
                $workerId,
                $pid,
                count($batch)
            ));
        }

        // Parent: wait for all children
        $this->waitForChildren();

        $this->logger?->info(sprintf(
            'Parallel rendering complete: %d workers, %d documents',
            count($this->childPids),
            $documentCount
        ));

        $this->totalRendered = $documentCount;
    }

    /**
     * Determine if we should fork based on conditions.
     */
    private function shouldFork(int $documentCount): bool
    {
        // Must have parallel enabled
        if (!$this->parallelEnabled) {
            return false;
        }

        // Must have pcntl extension
        if (!function_exists('pcntl_fork')) {
            return false;
        }

        // Must have enough documents to make it worthwhile
        if ($documentCount < self::MIN_DOCS_FOR_PARALLEL) {
            return false;
        }

        // Must have more than one worker configured
        if ($this->workerCount < 2) {
            return false;
        }

        return true;
    }

    /**
     * Partition documents into batches for workers.
     *
     * @param DocumentNode[] $documents
     * @return array<int, DocumentNode[]>
     */
    private function partitionDocuments(array $documents, int $workerCount): array
    {
        $batchSize = (int) ceil(count($documents) / $workerCount);
        return array_chunk($documents, max(1, $batchSize));
    }

    /**
     * Render a batch of documents in a child process.
     *
     * Navigation is handled by DocumentNavigationProvider which was initialized
     * before forking and is inherited by this child process via copy-on-write.
     * TwigExtension checks the provider for prev/next navigation.
     *
     * @param DocumentNode[] $batch Documents to render in this child
     */
    private function renderChildBatch(
        array $batch,
        RenderCommand $renderCommand,
        int $workerId,
    ): void {
        // Build render context with the original iterator
        // Navigation is handled by DocumentNavigationProvider, not the iterator
        $context = RenderContext::forProject(
            $renderCommand->getProjectNode(),
            $renderCommand->getDocumentArray(),
            $renderCommand->getOrigin(),
            $renderCommand->getDestination(),
            $renderCommand->getDestinationPath(),
            $renderCommand->getOutputFormat(),
        )->withIterator($renderCommand->getDocumentIterator());

        // Render assigned documents
        foreach ($batch as $document) {
            try {
                $this->commandBus->handle(
                    new RenderDocumentCommand($document, $context->withDocument($document))
                );
            } catch (\Throwable $e) {
                // Log error and continue with other documents
                // Error output goes to stderr which parent can capture
                fwrite(STDERR, sprintf(
                    "[Worker %d] Error rendering %s: %s\n",
                    $workerId,
                    $document->getFilePath(),
                    $e->getMessage()
                ));
            }
        }
    }

    /**
     * Render documents sequentially (fallback).
     *
     * Uses the same approach as BaseTypeRenderer to maintain proper
     * prev/next navigation links through the iterator.
     */
    private function renderSequentially(RenderCommand $renderCommand): void
    {
        $context = RenderContext::forProject(
            $renderCommand->getProjectNode(),
            $renderCommand->getDocumentArray(),
            $renderCommand->getOrigin(),
            $renderCommand->getDestination(),
            $renderCommand->getDestinationPath(),
            $renderCommand->getOutputFormat(),
        )->withIterator($renderCommand->getDocumentIterator());

        $count = 0;
        foreach ($context->getIterator() as $document) {
            $this->commandBus->handle(
                new RenderDocumentCommand(
                    $document,
                    $context->withDocument($document),
                )
            );
            $count++;
        }

        $this->totalRendered = $count;
    }

    /**
     * Render from a pre-built documents array.
     *
     * Used when the iterator has already been exhausted (e.g., after failed fork).
     * Note: This loses prev/next navigation context since we're not using the iterator.
     *
     * @param DocumentNode[] $documents
     */
    private function renderFromArray(RenderCommand $renderCommand, array $documents): void
    {
        $context = RenderContext::forProject(
            $renderCommand->getProjectNode(),
            $renderCommand->getDocumentArray(),
            $renderCommand->getOrigin(),
            $renderCommand->getDestination(),
            $renderCommand->getDestinationPath(),
            $renderCommand->getOutputFormat(),
        )->withIterator($renderCommand->getDocumentIterator());

        foreach ($documents as $document) {
            $this->commandBus->handle(
                new RenderDocumentCommand(
                    $document,
                    $context->withDocument($document),
                )
            );
        }

        $this->totalRendered = count($documents);
    }

    /**
     * Wait for all child processes to complete.
     *
     * @throws \RuntimeException If any child process fails
     */
    private function waitForChildren(): void
    {
        $failures = [];

        foreach ($this->childPids as $workerId => $pid) {
            $status = 0;
            $waitedPid = pcntl_waitpid($pid, $status);

            if ($waitedPid === -1) {
                $failures[$workerId] = 'waitpid failed';
                continue;
            }

            // pcntl_waitpid always sets $status to an int
            assert(is_int($status));

            if (pcntl_wifexited($status)) {
                $exitCode = pcntl_wexitstatus($status);
                if ($exitCode !== 0) {
                    $failures[$workerId] = sprintf('exit code %d', $exitCode);
                }
            } elseif (pcntl_wifsignaled($status)) {
                $signal = pcntl_wtermsig($status);
                $failures[$workerId] = sprintf('killed by signal %d', $signal);
            }
        }

        if ($failures !== []) {
            $errorDetails = [];
            foreach ($failures as $workerId => $reason) {
                $errorDetails[] = sprintf('Worker %d: %s', $workerId, $reason);
            }
            throw new \RuntimeException(
                'Parallel rendering failed: ' . implode(', ', $errorDetails)
            );
        }
    }

    /**
     * Detect number of CPU cores.
     */
    private function detectCpuCount(): int
    {
        // Try /proc/cpuinfo on Linux
        if (is_file('/proc/cpuinfo')) {
            $cpuinfo = file_get_contents('/proc/cpuinfo');
            if ($cpuinfo !== false) {
                $count = substr_count($cpuinfo, 'processor');
                if ($count > 0) {
                    return min($count, 8); // Cap at 8 workers
                }
            }
        }

        // Try nproc command
        $nproc = @shell_exec('nproc 2>/dev/null');
        if ($nproc !== null && $nproc !== false) {
            $count = (int) trim($nproc);
            if ($count > 0) {
                return min($count, 8);
            }
        }

        // Try sysctl on macOS
        $sysctl = @shell_exec('sysctl -n hw.ncpu 2>/dev/null');
        if ($sysctl !== null && $sysctl !== false) {
            $count = (int) trim($sysctl);
            if ($count > 0) {
                return min($count, 8);
            }
        }

        // Default to 4 workers
        return 4;
    }

    /**
     * Set the number of worker processes.
     */
    public function setWorkerCount(int $count): void
    {
        $this->workerCount = max(1, min($count, 16)); // Bound between 1 and 16
    }

    /**
     * Get the configured worker count.
     */
    public function getWorkerCount(): int
    {
        return $this->workerCount;
    }

    /**
     * Enable or disable parallel rendering.
     */
    public function setParallelEnabled(bool $enabled): void
    {
        $this->parallelEnabled = $enabled;
    }

    /**
     * Check if parallel rendering is enabled.
     */
    public function isParallelEnabled(): bool
    {
        return $this->parallelEnabled;
    }

    /**
     * Get the total number of documents rendered in the last run.
     */
    public function getTotalRendered(): int
    {
        return $this->totalRendered;
    }
}
