<?php

declare(strict_types=1);

namespace T3Docs\GuidesExtension\Parser;

use League\Tactician\CommandBus;
use phpDocumentor\Guides\Event\PostCollectFilesForParsingEvent;
use phpDocumentor\Guides\Event\PostParseProcess;
use phpDocumentor\Guides\Event\PreParseProcess;
use phpDocumentor\Guides\FileCollector;
use phpDocumentor\Guides\Files;
use phpDocumentor\Guides\Handlers\ParseDirectoryCommand;
use phpDocumentor\Guides\Handlers\ParseFileCommand;
use phpDocumentor\Guides\Nodes\DocumentNode;
use phpDocumentor\Guides\Settings\ProjectSettings;
use phpDocumentor\Guides\Settings\SettingsManager;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\LoggerInterface;
use T3Docs\GuidesExtension\Util\CpuDetector;
use T3Docs\GuidesExtension\Util\ProcessManager;

use function array_chunk;
use function array_map;
use function assert;
use function count;
use function explode;
use function iterator_to_array;
use function file_get_contents;
use function file_put_contents;
use function function_exists;
use function pcntl_fork;
use function serialize;
use function unserialize;

/**
 * Parallel version of ParseDirectoryHandler using pcntl_fork.
 *
 * Parallelizes the parsing phase by forking child processes that each parse
 * a batch of files. Results are serialized to temp files and collected by
 * the parent process.
 */
final class ParallelParseDirectoryHandler
{
    private const int MIN_FILES_FOR_PARALLEL = 10;

    private SettingsManager $settingsManager;
    private int $workerCount;

    public function __construct(
        private readonly FileCollector $fileCollector,
        private readonly CommandBus $commandBus,
        private readonly EventDispatcherInterface $eventDispatcher,
        private readonly ?LoggerInterface $logger = null,
        ?SettingsManager $settingsManager = null,
        ?int $workerCount = null,
    ) {
        $this->settingsManager = $settingsManager ?? new SettingsManager(new ProjectSettings());
        $this->workerCount = $workerCount ?? $this->detectCpuCount();
    }

    /** @return DocumentNode[] */
    public function handle(ParseDirectoryCommand $command): array
    {
        $preParseProcessEvent = $this->eventDispatcher->dispatch(
            new PreParseProcess($command),
        );
        assert($preParseProcessEvent instanceof PreParseProcess);
        $command = $preParseProcessEvent->getParseDirectoryCommand();

        $origin = $command->getOrigin();
        $currentDirectory = $command->getDirectory();
        $extension = $command->getInputFormat();

        $indexName = $this->getDirectoryIndexFile($command);

        $files = $this->fileCollector->collect(
            $origin,
            $currentDirectory,
            $extension,
            $command->hasExclude() ? $command->getExclude() : $command->getExcludedSpecification(),
        );

        $postCollectFilesForParsingEvent = $this->eventDispatcher->dispatch(
            new PostCollectFilesForParsingEvent($command, $files),
        );
        assert($postCollectFilesForParsingEvent instanceof PostCollectFilesForParsingEvent);
        $filesCollection = $postCollectFilesForParsingEvent->getFiles();

        // Convert Files to array for parallel processing
        $fileList = iterator_to_array($filesCollection->getIterator(), false);

        // Decide whether to use parallel or sequential parsing
        if (!$this->shouldFork(count($fileList))) {
            $documents = $this->parseSequentially($command, $fileList, $indexName);
        } else {
            $documents = $this->parseInParallel($command, $fileList, $indexName);
        }

        $postParseEvent = $this->eventDispatcher->dispatch(
            new PostParseProcess($command, $documents),
        );
        assert($postParseEvent instanceof PostParseProcess);

        return $documents;
    }

    /**
     * Parse files sequentially (fallback).
     *
     * @param string[] $files
     * @return DocumentNode[]
     */
    private function parseSequentially(ParseDirectoryCommand $command, array $files, string $indexName): array
    {
        $documents = [];
        foreach ($files as $file) {
            $doc = $this->commandBus->handle(
                new ParseFileCommand(
                    $command->getOrigin(),
                    $command->getDirectory(),
                    $file,
                    $command->getInputFormat(),
                    1,
                    $command->getProjectNode(),
                    $indexName === $file,
                ),
            );
            if ($doc instanceof DocumentNode) {
                $documents[] = $doc;
            }
        }

        return $documents;
    }

    /**
     * Parse files in parallel using pcntl_fork.
     *
     * @param string[] $files
     * @return DocumentNode[]
     */
    private function parseInParallel(ParseDirectoryCommand $command, array $files, string $indexName): array
    {
        $this->logger?->info(sprintf(
            'Starting parallel parsing: %d files across %d workers',
            count($files),
            $this->workerCount
        ));

        // Partition files into batches
        $batchSize = (int) ceil(count($files) / $this->workerCount);
        $batches = array_chunk($files, max(1, $batchSize));

        // Create temp files for each worker's results
        $tempFiles = [];
        $childPids = [];

        foreach ($batches as $workerId => $batch) {
            if ($batch === []) {
                continue;
            }

            // Create secure temp file for this worker's results
            $tempFile = ProcessManager::createSecureTempFile('parse_' . $workerId . '_');
            if ($tempFile === false) {
                $this->logger?->error('Failed to create temp file, falling back to sequential');
                return $this->parseSequentially($command, $files, $indexName);
            }
            $tempFiles[$workerId] = $tempFile;

            $pid = pcntl_fork();

            if ($pid === -1) {
                // Fork failed - clean up and fall back
                $this->logger?->error('pcntl_fork failed, falling back to sequential parsing');
                foreach ($tempFiles as $tf) {
                    ProcessManager::cleanupTempFile($tf);
                }
                return $this->parseSequentially($command, $files, $indexName);
            }

            if ($pid === 0) {
                // Child process: clear inherited temp file tracking to prevent
                // cleanup of parent's temp files when this child exits
                ProcessManager::clearTempFileTracking();

                // Parse batch and write results to temp file
                $this->parseChildBatch($command, $batch, $indexName, $tempFile);
                exit(0);
            }

            // Parent: record child PID
            $childPids[$workerId] = $pid;
        }

        // Parent: wait for all children with timeout and collect results
        $waitResult = ProcessManager::waitForChildrenWithTimeout($childPids);
        $documents = [];

        foreach ($childPids as $workerId => $pid) {
            // Only read results from successful workers
            if (in_array($workerId, $waitResult['successes'], true)) {
                $serialized = file_get_contents($tempFiles[$workerId]);
                if ($serialized !== false && $serialized !== '') {
                    $batchDocs = unserialize($serialized);
                    if (is_array($batchDocs)) {
                        foreach ($batchDocs as $doc) {
                            if ($doc instanceof DocumentNode) {
                                $documents[] = $doc;
                            }
                        }
                    }
                }
            }

            // Clean up temp file
            ProcessManager::cleanupTempFile($tempFiles[$workerId]);
        }

        if ($waitResult['failures'] !== []) {
            foreach ($waitResult['failures'] as $workerId => $reason) {
                $this->logger?->warning(sprintf('Parse worker %d failed: %s', $workerId, $reason));
            }
        }

        $this->logger?->info(sprintf(
            'Parallel parsing complete: %d documents from %d files',
            count($documents),
            count($files)
        ));

        return $documents;
    }

    /**
     * Parse a batch of files in a child process.
     *
     * @param string[] $batch
     */
    private function parseChildBatch(
        ParseDirectoryCommand $command,
        array $batch,
        string $indexName,
        string $tempFile,
    ): void {
        $documents = [];

        foreach ($batch as $file) {
            try {
                $doc = $this->commandBus->handle(
                    new ParseFileCommand(
                        $command->getOrigin(),
                        $command->getDirectory(),
                        $file,
                        $command->getInputFormat(),
                        1,
                        $command->getProjectNode(),
                        $indexName === $file,
                    ),
                );
                if ($doc instanceof DocumentNode) {
                    $documents[] = $doc;
                }
            } catch (\Throwable $e) {
                // Log error and continue
                fwrite(STDERR, sprintf(
                    "Error parsing %s: %s\n",
                    $file,
                    $e->getMessage()
                ));
            }
        }

        // Serialize results to temp file
        file_put_contents($tempFile, serialize($documents));
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

    private function getDirectoryIndexFile(ParseDirectoryCommand $command): string
    {
        $filesystem = $command->getOrigin();
        $directory = $command->getDirectory();
        $extension = $command->getInputFormat();

        $contentFromFilesystem = $filesystem->listContents($directory);
        $hashedContentFromFilesystem = [];
        /** @var \ArrayAccess<string, mixed> $itemFromFilesystem */
        foreach ($contentFromFilesystem as $itemFromFilesystem) {
            // Use array access as phpDocumentor's FileAttributes wrapper supports it
            /** @var string $basename */
            $basename = $itemFromFilesystem['basename'];
            $hashedContentFromFilesystem[$basename] = true;
        }

        $indexFileNames = array_map('trim', explode(',', $this->settingsManager->getProjectSettings()->getIndexName()));

        foreach ($indexFileNames as $indexName) {
            $fullIndexFilename = sprintf('%s.%s', $indexName, $extension);
            if (isset($hashedContentFromFilesystem[$fullIndexFilename])) {
                return $indexName;
            }
        }

        // Default to first index name if not found (error will be caught elsewhere)
        return $indexFileNames[0] ?? 'Index';
    }

    private function detectCpuCount(): int
    {
        return CpuDetector::detectCores();
    }
}
