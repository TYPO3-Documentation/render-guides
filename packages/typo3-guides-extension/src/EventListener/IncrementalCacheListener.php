<?php

declare(strict_types=1);

namespace T3Docs\GuidesExtension\EventListener;

use phpDocumentor\Guides\Files;
use phpDocumentor\Guides\Handlers\ParseDirectoryCommand;
use phpDocumentor\Guides\Event\PostCollectFilesForParsingEvent;
use phpDocumentor\Guides\Event\PostProjectNodeCreated;
use phpDocumentor\Guides\Event\PostRenderProcess;
use phpDocumentor\Guides\Meta\InternalTarget;
use phpDocumentor\Guides\Nodes\ProjectNode;
use phpDocumentor\Guides\Settings\ProjectSettings;
use T3Docs\GuidesExtension\Compiler\Cache\ChangeDetector;
use T3Docs\GuidesExtension\Compiler\Cache\ChangeDetectionResult;
use T3Docs\GuidesExtension\Compiler\Cache\ContentHasher;
use T3Docs\GuidesExtension\Compiler\Cache\DocumentExports;
use T3Docs\GuidesExtension\Compiler\Cache\GlobalInvalidationDetector;
use T3Docs\GuidesExtension\Compiler\Cache\IncrementalBuildCache;

/**
 * Event listener for incremental rendering cache management.
 *
 * Cache is stored as _build_meta.json in the output directory, ensuring
 * the cache stays in sync with the rendered output.
 *
 * Handles:
 * - Loading cache at project start (PostProjectNodeCreated)
 * - Detecting changes and filtering files to parse (PostCollectFilesForParsingEvent)
 * - Saving cache after rendering (via explicit call)
 */
final class IncrementalCacheListener
{
    private ?string $outputDir = null;
    private ?string $inputDir = null;
    private bool $incrementalEnabled = true;

    /** @var array<string, DocumentExports> Old exports before recompilation */
    private array $oldExports = [];

    /** @var string Settings hash computed during parsing phase */
    private string $parsingSettingsHash = '';

    private ?ChangeDetectionResult $changeResult = null;

    private ?ProjectNode $projectNode = null;

    /** @var string[] Files that were skipped (not parsed) */
    private array $skippedFiles = [];

    /** @var string[]|null Batch filter for worker subprocess mode */
    private ?array $batchFilter = null;

    /** @var int|null Worker ID for subprocess mode */
    private ?int $workerId = null;

    public function __construct(
        private readonly IncrementalBuildCache $cache,
        private readonly ChangeDetector $changeDetector,
        private readonly ContentHasher $hasher,
        private readonly GlobalInvalidationDetector $invalidationDetector,
    ) {}

    /**
     * Handle PostProjectNodeCreated: Load cache from output directory.
     */
    public function onPostProjectNodeCreated(PostProjectNodeCreated $event): void
    {
        $settings = $event->getSettings();
        $this->outputDir = $this->getOutputDirectory($settings);
        $this->inputDir = $settings->getInput();
        $this->projectNode = $event->getProjectNode();

        // Store input directory in cache for ExportsCollectorPass to use
        $this->cache->setInputDir($this->inputDir);

        // Try to load the cache from output directory
        $loaded = $this->cache->load($this->outputDir);

        if (!$loaded) {
            // Cache not found or invalid - full rebuild needed
            $this->incrementalEnabled = false;
            $this->oldExports = [];
        } else {
            // Save a copy of the old exports before they get updated during compilation
            $this->oldExports = $this->cache->getAllExports();
        }
    }

    /**
     * Handle PostCollectFilesForParsingEvent: Detect changes, pre-populate cache, filter files.
     *
     * For incremental parsing optimization:
     * 1. Detect which files have changed
     * 2. Pre-populate ProjectNode with cached link targets for unchanged files
     * 3. Filter file list to only include changed files (skip parsing unchanged)
     */
    public function onPostCollectFilesForParsing(PostCollectFilesForParsingEvent $event): void
    {
        // Always compute settings hash for cache saving
        $this->parsingSettingsHash = $this->computeSettingsHash($event->getCommand());

        // Handle worker subprocess mode - filter files even without incremental cache
        if ($this->batchFilter !== null) {
            $this->applyBatchFilterOnly($event);
            return;
        }

        if (!$this->incrementalEnabled) {
            // No cache - process all files normally
            return;
        }

        // Get all source files
        $files = $event->getFiles();
        $command = $event->getCommand();

        // Use the input directory saved from PostProjectNodeCreated event
        $inputDir = $this->inputDir ?? '';

        $inputFormat = $command->getInputFormat();
        $extension = $inputFormat === 'rst' ? '.rst' : '.md';

        // Get document paths
        $documentPaths = iterator_to_array($files->getIterator());

        // Detect changes using document paths but provide file resolver for hashing
        $this->changeResult = $this->changeDetector->detectChangesWithResolver(
            $documentPaths,
            $this->oldExports,
            fn($docPath) => $inputDir . '/' . $docPath . $extension
        );

        // Check for global invalidation (config changes, theme changes, etc.)
        $cachedHash = $this->cache->getSettingsHash();

        if ($this->invalidationDetector->requiresFullRebuild(
            $this->changeResult,
            $this->parsingSettingsHash,
            $cachedHash
        )) {
            // Full rebuild required - don't filter anything
            $this->incrementalEnabled = false;
            $this->changeResult = null;
            return;
        }

        // Pre-populate ProjectNode with cached link targets for unchanged files
        $this->prepopulateCachedTargets();

        // At this point, changeResult is guaranteed non-null (set above, only nulled on early return)
        assert($this->changeResult !== null);

        // Filter to only parse changed files
        $filesToParse = $this->changeResult->getFilesToProcess();

        // Apply batch filter if in worker subprocess mode
        if ($this->batchFilter !== null) {
            // In worker mode: only process documents assigned to this worker
            $filesToParse = array_intersect($filesToParse, $this->batchFilter);
        } else {
            // In main process mode: always include root index document
            // This is required for rendering even if index hasn't changed
            // Find the actual index document path (case-insensitive match for "index" or "Index")
            $indexDoc = null;
            foreach ($documentPaths as $docPath) {
                if (strcasecmp($docPath, 'index') === 0) {
                    $indexDoc = $docPath;
                    break;
                }
            }
            if ($indexDoc !== null && !in_array($indexDoc, $filesToParse, true)) {
                $filesToParse[] = $indexDoc;
            }
        }

        $this->skippedFiles = array_diff($documentPaths, $filesToParse);

        // Update the file iterator to only include files that need parsing
        $newFiles = new Files();
        foreach ($filesToParse as $filePath) {
            $newFiles->add($filePath);
        }
        $event->setFiles($newFiles);
    }

    /**
     * Apply batch filter for worker subprocess mode (without incremental logic).
     * Used when a worker needs to process only its assigned documents.
     */
    private function applyBatchFilterOnly(PostCollectFilesForParsingEvent $event): void
    {
        $files = $event->getFiles();
        $documentPaths = iterator_to_array($files->getIterator());

        // Filter to only include documents in the batch
        $filesToParse = array_intersect($documentPaths, $this->batchFilter ?? []);

        $this->skippedFiles = array_diff($documentPaths, $filesToParse);

        // Pre-populate cached targets for documents not in batch (for cross-refs)
        $this->prepopulateCachedTargetsForBatch($filesToParse);

        // Update the file iterator
        $newFiles = new Files();
        foreach ($filesToParse as $filePath) {
            $newFiles->add($filePath);
        }
        $event->setFiles($newFiles);
    }

    /**
     * Pre-populate cached targets for documents not being processed in this batch.
     * This allows cross-reference resolution to work in worker subprocess mode.
     *
     * @param string[] $batchFiles Files being processed in this batch
     */
    private function prepopulateCachedTargetsForBatch(array $batchFiles): void
    {
        if ($this->projectNode === null) {
            return;
        }

        // Get all cached exports (from documents not in this batch)
        $allExports = $this->cache->getAllExports();

        foreach ($allExports as $docPath => $exports) {
            // Skip documents that are in this batch (they'll be freshly parsed)
            if (in_array($docPath, $batchFiles, true)) {
                continue;
            }

            // Add cached internal targets to ProjectNode
            foreach ($exports->internalTargets as $targetData) {
                if (!is_array($targetData)) {
                    continue;
                }

                $anchorName = $targetData['anchorName'] ?? '';
                $title = $targetData['title'] ?? null;
                $linkType = $targetData['linkType'] ?? '';
                $prefix = $targetData['prefix'] ?? '';

                if ($anchorName === '') {
                    continue;
                }

                try {
                    $this->projectNode->addLinkTarget(
                        $anchorName,
                        new InternalTarget(
                            $docPath,
                            $anchorName,
                            $title,
                            $linkType,
                            $prefix,
                        )
                    );
                } catch (\Exception) {
                    // Ignore duplicate target errors
                }
            }
        }
    }

    /**
     * Pre-populate ProjectNode with cached InternalTargets for unchanged files.
     * This allows cross-reference resolution without parsing unchanged documents.
     */
    private function prepopulateCachedTargets(): void
    {
        if ($this->projectNode === null || $this->changeResult === null) {
            return;
        }

        // Get files that don't need parsing (clean = unchanged)
        $unchangedFiles = $this->changeResult->clean;

        foreach ($unchangedFiles as $docPath) {
            $exports = $this->oldExports[$docPath] ?? null;
            if ($exports === null) {
                continue;
            }

            // Add cached internal targets to ProjectNode
            foreach ($exports->internalTargets as $targetData) {
                if (!is_array($targetData)) {
                    continue;
                }

                $anchorName = $targetData['anchorName'] ?? '';
                $title = $targetData['title'] ?? null;
                $linkType = $targetData['linkType'] ?? '';
                $prefix = $targetData['prefix'] ?? '';

                if ($anchorName === '') {
                    continue;
                }

                try {
                    $this->projectNode->addLinkTarget(
                        $anchorName,
                        new InternalTarget(
                            $docPath,
                            $anchorName,
                            $title,
                            $linkType,
                            $prefix,
                        )
                    );
                } catch (\Exception) {
                    // Ignore duplicate target errors
                }
            }
        }
    }

    /**
     * Handle PostRenderProcess: Save cache after all rendering is complete.
     */
    public function onPostRenderProcess(PostRenderProcess $event): void
    {
        // Use the parsing settings hash computed earlier to ensure consistency
        $this->saveCache($this->parsingSettingsHash);
    }

    /**
     * Save the cache to output directory.
     * Called after rendering completes.
     */
    public function saveCache(string $settingsHash = ''): void
    {
        if ($this->outputDir === null) {
            return;
        }

        $this->cache->save($this->outputDir, $settingsHash);
    }

    /**
     * Get the output directory for this project.
     */
    private function getOutputDirectory(ProjectSettings $settings): string
    {
        $output = $settings->getOutput();
        if ($output === '') {
            $output = getcwd() . '/Documentation-GENERATED-temp';
        }

        return $output;
    }

    /**
     * Compute a hash of relevant settings.
     */
    private function computeSettingsHash(ParseDirectoryCommand $command): string
    {
        // Hash key settings that affect rendering
        $settings = [
            'input' => $command->getDirectory(),
            'inputFormat' => $command->getInputFormat(),
        ];

        return $this->hasher->hashContent(serialize($settings));
    }

    /**
     * Check if incremental rendering is currently enabled.
     */
    public function isIncrementalEnabled(): bool
    {
        return $this->incrementalEnabled;
    }

    /**
     * Get the build cache.
     */
    public function getCache(): IncrementalBuildCache
    {
        return $this->cache;
    }

    /**
     * Get the old exports (from before recompilation).
     *
     * @return array<string, DocumentExports>
     */
    public function getOldExports(): array
    {
        return $this->oldExports;
    }

    /**
     * Get the change detection result.
     */
    public function getChangeResult(): ?ChangeDetectionResult
    {
        return $this->changeResult;
    }

    /**
     * Get the list of files that were skipped (not parsed).
     *
     * @return string[]
     */
    public function getSkippedFiles(): array
    {
        return $this->skippedFiles;
    }

    /**
     * Compute the final dirty set after compilation.
     * This compares old exports vs new exports and propagates through dependency graph.
     *
     * @return string[] Documents that need rendering
     */
    public function computeDirtySet(): array
    {
        if (!$this->incrementalEnabled || $this->changeResult === null) {
            // Not incremental - render all
            return [];
        }

        $graph = $this->cache->getDependencyGraph();
        $newExports = $this->cache->getAllExports();

        // Documents that need rendering (content changed)
        $contentChangedDocs = $this->changeResult->getFilesToProcess();

        // Documents that need propagation (exports changed or deleted)
        $docsNeedingPropagation = [];

        // Check which dirty docs have changed exports
        foreach ($this->changeResult->dirty as $docPath) {
            $old = $this->oldExports[$docPath] ?? null;
            $new = $newExports[$docPath] ?? null;

            if ($old !== null && $new !== null && $old->hasExportsChanged($new)) {
                // Exports changed - this doc's dependents need re-rendering
                $docsNeedingPropagation[] = $docPath;
            }
        }

        // Deleted files also need propagation to dependents
        foreach ($this->changeResult->deleted as $deletedPath) {
            $docsNeedingPropagation[] = $deletedPath;
        }

        // Only propagate from docs with changed exports, not all content changes
        $propagatedDirty = [];
        if ($docsNeedingPropagation !== []) {
            $propagatedDirty = $graph->propagateDirty($docsNeedingPropagation);
        }

        // Combine: content-changed docs + propagated dependents
        return array_unique(array_merge($contentChangedDocs, $propagatedDirty));
    }

    /**
     * Set batch filter for worker subprocess mode.
     * When set, only documents in the batch will be processed.
     *
     * @param string[] $documents Document paths to process in this batch
     * @param int $workerId Worker identifier
     */
    public function setBatchFilter(array $documents, int $workerId): void
    {
        $this->batchFilter = $documents;
        $this->workerId = $workerId;
    }

    /**
     * Check if running in worker subprocess mode.
     */
    public function isWorkerMode(): bool
    {
        return $this->batchFilter !== null;
    }

    /**
     * Get the worker ID (null if not in worker mode).
     */
    public function getWorkerId(): ?int
    {
        return $this->workerId;
    }

    /**
     * Get the batch filter (null if not in worker mode).
     *
     * @return string[]|null
     */
    public function getBatchFilter(): ?array
    {
        return $this->batchFilter;
    }
}
