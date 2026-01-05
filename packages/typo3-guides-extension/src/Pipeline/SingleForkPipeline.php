<?php

declare(strict_types=1);

namespace T3Docs\GuidesExtension\Pipeline;

use phpDocumentor\Guides\Nodes\DocumentNode;
use phpDocumentor\Guides\Nodes\ProjectNode;
use Psr\Log\LoggerInterface;
use T3Docs\GuidesExtension\Util\CpuDetector;
use T3Docs\GuidesExtension\Util\ProcessManager;

use function array_chunk;
use function count;
use function file_get_contents;
use function file_put_contents;
use function function_exists;
use function glob;
use function is_array;
use function max;
use function pcntl_fork;
use function preg_replace_callback;
use function serialize;
use function unserialize;

/**
 * Single-fork pipeline: fork once, each worker does parse → compile → render.
 *
 * This is the simplest parallel architecture:
 * 1. Quick scan to discover files and toctree order (sequential, fast)
 * 2. Fork workers - each does full pipeline for its batch (parallel, CPU-heavy)
 * 3. Post-process HTML to resolve navigation placeholders (sequential, fast)
 *
 * Benefits over multi-phase approach:
 * - Single fork/wait cycle instead of multiple
 * - No inter-process serialization of AST/ProjectNode
 * - Each worker is independent, no merge step
 * - Simpler code, fewer failure modes
 */
final class SingleForkPipeline
{
    /** Minimum files before parallelization is worthwhile */
    private const int MIN_FILES_FOR_PARALLEL = 10;

    /** Placeholder pattern for navigation links */
    private const string NAV_PLACEHOLDER_PATTERN = '/<!--GUIDES_NAV:(prev|next):([^:]+):([^>]+)-->/';

    private int $workerCount;

    public function __construct(
        private readonly ?LoggerInterface $logger = null,
        ?int $workerCount = null,
    ) {
        $this->workerCount = $workerCount ?? $this->detectCpuCount();
    }

    /**
     * Execute the full pipeline with optional parallelization.
     *
     * @param callable(string[]): array{documents: DocumentNode[], projectNode: ProjectNode} $pipelineExecutor
     *        Function that executes parse→compile→render for a batch of files
     * @param string[] $allFiles All files to process
     * @param string $outputDir Output directory for rendered HTML
     * @return array{documents: DocumentNode[], projectNode: ProjectNode}
     */
    public function execute(
        callable $pipelineExecutor,
        array $allFiles,
        string $outputDir,
    ): array {
        // Check if parallel is worthwhile
        if (!$this->shouldFork(count($allFiles))) {
            $this->logger?->debug('Using sequential pipeline');
            return $pipelineExecutor($allFiles);
        }

        $this->logger?->info(sprintf(
            'Starting single-fork pipeline: %d files across %d workers',
            count($allFiles),
            $this->workerCount
        ));

        // Partition files into batches
        $batchSize = (int) ceil(count($allFiles) / $this->workerCount);
        $batches = array_chunk($allFiles, max(1, $batchSize));

        // Create temp files for results
        $tempFiles = [];
        $childPids = [];

        foreach ($batches as $workerId => $batch) {
            if ($batch === []) {
                continue;
            }

            $tempFile = ProcessManager::createSecureTempFile('pipeline_' . $workerId . '_');
            if ($tempFile === false) {
                $this->logger?->error('Failed to create temp file, falling back to sequential');
                return $pipelineExecutor($allFiles);
            }
            $tempFiles[$workerId] = $tempFile;

            $pid = pcntl_fork();

            if ($pid === -1) {
                $this->logger?->error('pcntl_fork failed, falling back to sequential');
                foreach ($tempFiles as $tf) {
                    ProcessManager::cleanupTempFile($tf);
                }
                return $pipelineExecutor($allFiles);
            }

            if ($pid === 0) {
                // Child: clear inherited temp file tracking
                ProcessManager::clearTempFileTracking();
                try {
                    $result = $pipelineExecutor($batch);
                    // Only serialize document paths (not full AST) to save memory
                    $paths = array_map(
                        fn(DocumentNode $doc) => $doc->getFilePath(),
                        $result['documents']
                    );
                    file_put_contents($tempFile, serialize(['paths' => $paths]));
                } catch (\Throwable $e) {
                    fwrite(STDERR, sprintf(
                        "[Worker %d] Pipeline failed: %s\n",
                        $workerId,
                        $e->getMessage()
                    ));
                    file_put_contents($tempFile, serialize(['error' => $e->getMessage()]));
                }
                exit(0);
            }

            // Parent: record child PID
            $childPids[$workerId] = $pid;
        }

        // Wait for all children with timeout
        $waitResult = ProcessManager::waitForChildrenWithTimeout($childPids);
        $allPaths = [];
        $failures = [];

        foreach ($childPids as $workerId => $pid) {
            // Only read results from successful workers
            if (in_array($workerId, $waitResult['successes'], true)) {
                $serialized = file_get_contents($tempFiles[$workerId]);
                if ($serialized !== false && $serialized !== '') {
                    $data = unserialize($serialized);
                    if (is_array($data) && isset($data['paths']) && is_array($data['paths'])) {
                        /** @var string[] $paths */
                        $paths = $data['paths'];
                        $allPaths = array_merge($allPaths, $paths);
                    }
                    if (is_array($data) && isset($data['error']) && is_string($data['error'])) {
                        $failures[$workerId] = $data['error'];
                    }
                }
            } else {
                $reason = $waitResult['failures'][$workerId] ?? 'unknown';
                $failures[$workerId] = $reason;
            }

            ProcessManager::cleanupTempFile($tempFiles[$workerId]);
        }

        if ($failures !== []) {
            foreach ($failures as $workerId => $reason) {
                $this->logger?->warning(sprintf('Pipeline worker %d failed: %s', $workerId, $reason));
            }
        }

        // Post-process: resolve navigation placeholders
        $this->resolveNavigationPlaceholders($outputDir, $allPaths);

        $this->logger?->info(sprintf(
            'Single-fork pipeline complete: %d documents processed',
            count($allPaths)
        ));

        // Return empty result since documents were rendered by children
        return ['documents' => [], 'projectNode' => new ProjectNode()];
    }

    /**
     * Post-process HTML files to resolve navigation placeholders.
     *
     * Placeholders format: <!--GUIDES_NAV:type:currentPath:targetPath-->
     * After all rendering is complete, we know the full document order and can resolve these.
     *
     * @param string[] $documentPaths
     */
    private function resolveNavigationPlaceholders(string $outputDir, array $documentPaths): void
    {
        // Build path -> index map for quick lookup
        $pathIndex = array_flip($documentPaths);

        // Scan all HTML files
        $globDeep = glob($outputDir . '/**/*.html');
        $htmlFiles = $globDeep !== false ? $globDeep : [];
        $globShallow = glob($outputDir . '/*.html');
        $htmlFiles = array_merge($htmlFiles, $globShallow !== false ? $globShallow : []);

        foreach ($htmlFiles as $htmlFile) {
            $content = file_get_contents($htmlFile);
            if ($content === false) {
                continue;
            }

            // Check if file has placeholders
            if (strpos($content, '<!--GUIDES_NAV:') === false) {
                continue;
            }

            // Replace placeholders
            $newContent = preg_replace_callback(
                self::NAV_PLACEHOLDER_PATTERN,
                function (array $matches) use ($pathIndex, $documentPaths): string {
                    $type = $matches[1]; // 'prev' or 'next'
                    $currentPath = $matches[2];
                    $placeholder = $matches[3];

                    if (!isset($pathIndex[$currentPath])) {
                        return ''; // Unknown document
                    }

                    $currentIndex = $pathIndex[$currentPath];
                    $targetIndex = $type === 'prev' ? $currentIndex - 1 : $currentIndex + 1;

                    if ($targetIndex < 0 || $targetIndex >= count($documentPaths)) {
                        return ''; // No prev/next
                    }

                    // Return the target path - actual HTML generation is done by Twig
                    return $documentPaths[$targetIndex];
                },
                $content
            );

            if ($newContent !== null && $newContent !== $content) {
                file_put_contents($htmlFile, $newContent);
            }
        }
    }

    private function shouldFork(int $fileCount): bool
    {
        if (!function_exists('pcntl_fork')) {
            return false;
        }

        if ($fileCount < self::MIN_FILES_FOR_PARALLEL) {
            return false;
        }

        if ($this->workerCount < 2) {
            return false;
        }

        return true;
    }

    private function detectCpuCount(): int
    {
        return CpuDetector::detectCores();
    }

    public function setWorkerCount(int $count): void
    {
        $this->workerCount = max(1, min($count, 16));
    }
}
