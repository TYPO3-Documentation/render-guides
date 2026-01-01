<?php

declare(strict_types=1);

namespace T3Docs\GuidesExtension\EventListener;

use phpDocumentor\Guides\Handlers\ParseDirectoryCommand;
use phpDocumentor\Guides\Event\PostCollectFilesForParsingEvent;
use phpDocumentor\Guides\Event\PostProjectNodeCreated;
use phpDocumentor\Guides\Event\PostRenderProcess;
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
     * Handle PostCollectFilesForParsingEvent: Detect changes and optionally filter files.
     *
     * Note: For proper incremental parsing, all files still need to be parsed
     * to maintain cross-reference integrity. However, we use this event to
     * detect changes for later use in rendering phase.
     */
    public function onPostCollectFilesForParsing(PostCollectFilesForParsingEvent $event): void
    {
        // Always compute settings hash for cache saving
        $this->parsingSettingsHash = $this->computeSettingsHash($event->getCommand());

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

        // Store the change detection result for use in rendering phase
        // The actual filtering happens in the render phase, not here,
        // because we need all documents parsed for cross-reference resolution.
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

        // Start with documents whose content changed
        $dirtyDocs = $this->changeResult->getFilesToProcess();

        // Add documents whose exports changed
        foreach ($this->changeResult->dirty as $docPath) {
            $old = $this->oldExports[$docPath] ?? null;
            $new = $newExports[$docPath] ?? null;

            if ($old !== null && $new !== null && $old->hasExportsChanged($new)) {
                // Exports changed - propagate to dependents
                $dependents = $graph->getDependents($docPath);
                $dirtyDocs = array_merge($dirtyDocs, $dependents);
            }
        }

        // Handle deleted files - their dependents need re-rendering
        foreach ($this->changeResult->deleted as $deletedPath) {
            $dependents = $graph->getDependents($deletedPath);
            $dirtyDocs = array_merge($dirtyDocs, $dependents);
        }

        // Propagate through the full graph
        $allDirty = $graph->propagateDirty($dirtyDocs);

        return array_unique($allDirty);
    }
}
