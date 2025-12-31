<?php

declare(strict_types=1);

namespace T3Docs\GuidesExtension\Pipeline;

use League\Tactician\CommandBus;
use phpDocumentor\FileSystem\FlySystemAdapter;
use phpDocumentor\Guides\Cli\Internal\RunCommand;
use phpDocumentor\Guides\Compiler\CompilerContext;
use phpDocumentor\Guides\Handlers\CompileDocumentsCommand;
use phpDocumentor\Guides\Handlers\ParseFileCommand;
use phpDocumentor\Guides\Handlers\RenderCommand;
use phpDocumentor\Guides\Nodes\DocumentNode;
use phpDocumentor\Guides\Twig\Theme\ThemeManager;
use Psr\Log\LoggerInterface;
use T3Docs\GuidesExtension\Renderer\Parallel\DocumentNavigationProvider;

use function array_chunk;
use function count;
use function file_put_contents;
use function function_exists;
use function is_int;
use function max;
use function pcntl_fork;
use function pcntl_waitpid;
use function pcntl_wifexited;
use function pcntl_wexitstatus;
use function serialize;
use function sprintf;
use function sys_get_temp_dir;
use function tempnam;
use function unlink;

/**
 * Single-fork pipeline: fork once, each worker does compile + render.
 *
 * Architecture:
 * 1. Parse ALL files (sequential, fast)
 * 2. Scan toctree order and reorder documents
 * 3. Fork workers: each compiles + renders its batch (parallel, CPU-heavy)
 *
 * Key insight: parsing is fast, compile+render is slow. By parsing all files
 * first we have the full document structure, then parallelize the heavy work.
 */
final class SingleForkRunHandler
{
    private const int MIN_FILES_FOR_PARALLEL = 10;

    private int $workerCount;

    public function __construct(
        private readonly CommandBus $commandBus,
        private readonly ThemeManager $themeManager,
        private readonly DocumentNavigationProvider $navigationProvider,
        private readonly ToctreeOrderScanner $orderScanner,
        private readonly ?LoggerInterface $logger = null,
        ?int $workerCount = null,
    ) {
        $this->workerCount = $workerCount ?? $this->detectCpuCount();
    }

    /**
     * @return DocumentNode[]
     */
    public function handle(RunCommand $command): array
    {
        $settings = $command->settings;
        $projectNode = $command->projectNode;

        // Single file input → sequential
        if ($settings->getInputFile() !== '') {
            return $this->handleSequential($command);
        }

        $sourceFileSystem = FlySystemAdapter::createForPath($settings->getInput());

        // Phase 1: Parse ALL files (needed for toctree structure)
        // This is fast compared to compile+render
        /** @var DocumentNode[] $documents */
        $documents = $this->commandBus->handle(
            new \phpDocumentor\Guides\Handlers\ParseDirectoryCommand(
                $sourceFileSystem,
                '',
                $settings->getInputFormat(),
                $projectNode,
                null,
            ),
        );

        if (!$this->shouldFork(count($documents))) {
            $this->logger?->debug('Using sequential pipeline');
            return $this->continueSequential($command, $documents);
        }

        $this->logger?->info(sprintf(
            'Single-fork pipeline: %d documents across %d workers',
            count($documents),
            $this->workerCount
        ));

        // Initialize theme before forking
        $this->themeManager->useTheme($settings->getTheme());

        // Scan toctree to get correct document order
        $toctreeOrder = $this->orderScanner->scan($sourceFileSystem, '', 'Index', $settings->getInputFormat());

        // Reorder documents according to toctree structure
        $orderedDocuments = $this->orderDocumentsByToctree($documents, $toctreeOrder);

        // Initialize navigation provider with correctly ordered documents BEFORE forking
        $this->navigationProvider->initializeFromArray($orderedDocuments);

        // Phase 2: Fork workers - each does compile + render for its batch
        $batchSize = (int) ceil(count($orderedDocuments) / $this->workerCount);
        $batches = array_chunk($orderedDocuments, max(1, $batchSize));

        $tempFiles = [];
        $childPids = [];
        $workerId = 0;

        foreach ($batches as $batch) {
            if ($batch === []) {
                $workerId++;
                continue;
            }

            $tempFile = tempnam(sys_get_temp_dir(), 'sfork_' . $workerId . '_');
            if ($tempFile === false) {
                $this->logger?->error('Failed to create temp file');
                return $this->continueSequential($command, $documents);
            }
            $tempFiles[$workerId] = $tempFile;

            $pid = pcntl_fork();

            if ($pid === -1) {
                $this->logger?->error('Fork failed');
                foreach ($tempFiles as $tf) {
                    @unlink($tf);
                }
                return $this->continueSequential($command, $documents);
            }

            if ($pid === 0) {
                // Child: compile + render this batch
                $this->executeWorkerCompileRender($command, $batch, $workerId, $tempFile);
                exit(0);
            }

            $childPids[$workerId] = $pid;
            $workerId++;
        }

        // Wait for all children
        $failures = [];
        foreach ($childPids as $wid => $pid) {
            $status = 0;
            pcntl_waitpid($pid, $status);

            if (is_int($status) && (!pcntl_wifexited($status) || pcntl_wexitstatus($status) !== 0)) {
                $failures[$wid] = 'process failed';
            }
            @unlink($tempFiles[$wid]);
        }

        if ($failures !== []) {
            $this->logger?->warning(sprintf('Workers failed: %s', implode(', ', array_keys($failures))));
        }

        $this->logger?->info('Single-fork pipeline complete');

        return $orderedDocuments;
    }

    /**
     * Reorder documents according to toctree structure.
     *
     * @param DocumentNode[] $documents Parsed documents (unordered)
     * @param string[] $toctreeOrder Document paths in toctree order
     * @return DocumentNode[] Documents reordered by toctree
     */
    private function orderDocumentsByToctree(array $documents, array $toctreeOrder): array
    {
        // Build path -> document map
        $docsByPath = [];
        foreach ($documents as $doc) {
            $docsByPath[$doc->getFilePath()] = $doc;
        }

        // Reorder according to toctree
        $ordered = [];
        foreach ($toctreeOrder as $path) {
            if (isset($docsByPath[$path])) {
                $ordered[] = $docsByPath[$path];
                unset($docsByPath[$path]);
            }
        }

        // Append any orphans not in toctree
        foreach ($docsByPath as $doc) {
            $ordered[] = $doc;
        }

        return $ordered;
    }

    /**
     * Worker process: compile + render a batch of already-parsed documents.
     *
     * @param DocumentNode[] $batch Documents assigned to this worker
     */
    private function executeWorkerCompileRender(
        RunCommand $command,
        array $batch,
        int $workerId,
        string $tempFile,
    ): void {
        $settings = $command->settings;
        $projectNode = $command->projectNode;

        try {
            $sourceFileSystem = FlySystemAdapter::createForPath($settings->getInput());

            // Compile our batch
            /** @var DocumentNode[] $compiledDocuments */
            $compiledDocuments = $this->commandBus->handle(
                new CompileDocumentsCommand($batch, new CompilerContext($projectNode))
            );

            // Render our batch
            $outputDir = $settings->getOutput();
            $destinationFileSystem = FlySystemAdapter::createForPath($outputDir);

            foreach ($settings->getOutputFormats() as $format) {
                $this->commandBus->handle(
                    new RenderCommand(
                        $format,
                        $compiledDocuments,
                        $sourceFileSystem,
                        $destinationFileSystem,
                        $projectNode,
                    ),
                );
            }

            // Report success
            file_put_contents($tempFile, serialize(['count' => count($compiledDocuments), 'workerId' => $workerId]));

        } catch (\Throwable $e) {
            fwrite(STDERR, sprintf("[Worker %d] Error: %s\n%s\n", $workerId, $e->getMessage(), $e->getTraceAsString()));
            file_put_contents($tempFile, serialize(['error' => $e->getMessage()]));
        }
    }

    /**
     * Continue sequential processing with already-parsed documents.
     *
     * @param DocumentNode[] $documents
     * @return DocumentNode[]
     */
    private function continueSequential(RunCommand $command, array $documents): array
    {
        $settings = $command->settings;
        $projectNode = $command->projectNode;
        $sourceFileSystem = FlySystemAdapter::createForPath($settings->getInput());

        $this->themeManager->useTheme($settings->getTheme());

        /** @var DocumentNode[] $compiledDocuments */
        $compiledDocuments = $this->commandBus->handle(
            new CompileDocumentsCommand($documents, new CompilerContext($projectNode))
        );

        $outputDir = $settings->getOutput();
        $destinationFileSystem = FlySystemAdapter::createForPath($outputDir);

        foreach ($settings->getOutputFormats() as $format) {
            $this->commandBus->handle(
                new RenderCommand(
                    $format,
                    $compiledDocuments,
                    $sourceFileSystem,
                    $destinationFileSystem,
                    $projectNode,
                ),
            );
        }

        return $compiledDocuments;
    }

    /**
     * Sequential fallback.
     *
     * @return DocumentNode[]
     */
    private function handleSequential(RunCommand $command): array
    {
        $settings = $command->settings;
        $projectNode = $command->projectNode;
        $sourceFileSystem = FlySystemAdapter::createForPath($settings->getInput());

        if ($settings->getInputFile() === '') {
            /** @var DocumentNode[] $documents */
            $documents = $this->commandBus->handle(
                new \phpDocumentor\Guides\Handlers\ParseDirectoryCommand(
                    $sourceFileSystem,
                    '',
                    $settings->getInputFormat(),
                    $projectNode,
                    null,
                ),
            );
        } else {
            /** @var DocumentNode $document */
            $document = $this->commandBus->handle(
                new ParseFileCommand(
                    $sourceFileSystem,
                    '',
                    $settings->getInputFile(),
                    $settings->getInputFormat(),
                    1,
                    $projectNode,
                    true,
                ),
            );
            $documents = [$document];
        }

        $this->themeManager->useTheme($settings->getTheme());

        /** @var DocumentNode[] $compiledDocuments */
        $compiledDocuments = $this->commandBus->handle(
            new CompileDocumentsCommand($documents, new CompilerContext($projectNode))
        );

        $outputDir = $settings->getOutput();
        $destinationFileSystem = FlySystemAdapter::createForPath($outputDir);

        foreach ($settings->getOutputFormats() as $format) {
            $this->commandBus->handle(
                new RenderCommand(
                    $format,
                    $compiledDocuments,
                    $sourceFileSystem,
                    $destinationFileSystem,
                    $projectNode,
                ),
            );
        }

        return $compiledDocuments;
    }

    private function shouldFork(int $fileCount): bool
    {
        return function_exists('pcntl_fork')
            && $fileCount >= self::MIN_FILES_FOR_PARALLEL
            && $this->workerCount >= 2;
    }

    private function detectCpuCount(): int
    {
        if (is_file('/proc/cpuinfo')) {
            $cpuinfo = file_get_contents('/proc/cpuinfo');
            if ($cpuinfo !== false) {
                $count = substr_count($cpuinfo, 'processor');
                if ($count > 0) {
                    return min($count, 8);
                }
            }
        }

        $nproc = @shell_exec('nproc 2>/dev/null');
        if ($nproc !== null && $nproc !== false) {
            $count = (int) trim($nproc);
            if ($count > 0) {
                return min($count, 8);
            }
        }

        return 4;
    }

    public function setWorkerCount(int $count): void
    {
        $this->workerCount = max(1, min($count, 16));
    }
}
