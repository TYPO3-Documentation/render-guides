<?php

declare(strict_types=1);

namespace T3Docs\GuidesExtension\Compiler\Cache;

/**
 * Central cache for incremental build state.
 *
 * Cache is sharded for performance:
 * - _build_meta.json: Metadata, dependency graph, output paths (small, always loaded)
 * - _exports/<hash>/<docPath>.json: Per-document exports (loaded on demand)
 *
 * Sharding benefits:
 * - O(1) save per changed document instead of O(n) full rewrite
 * - Better git diffs (only changed files appear)
 * - Reduced memory for large projects (can load exports on demand)
 */
final class IncrementalBuildCache
{
    private const BUILD_META_FILE = '_build_meta.json';
    private const EXPORTS_DIR = '_exports';

    /** @var array<string, DocumentExports> */
    private array $exports = [];

    private DependencyGraph $dependencyGraph;

    /** @var array<string, string> docPath -> rendered output path */
    private array $outputPaths = [];

    /** @var array<string, mixed> */
    private array $metadata = [];

    private bool $loaded = false;

    /** Input directory for file path resolution */
    private string $inputDir = '';

    /** @var array<string, true> Tracks which exports have been modified (for incremental save) */
    private array $dirtyExports = [];

    /** Output directory (stored for incremental saves) */
    private ?string $outputDir = null;

    public function __construct(
        private readonly CacheVersioning $versioning,
    ) {
        $this->dependencyGraph = new DependencyGraph();
    }

    /**
     * Load cache from output directory.
     *
     * Supports both legacy (monolithic) and sharded cache formats.
     *
     * @param string $outputDir The output directory where _build_meta.json is stored
     * @return bool True if cache was loaded and is valid
     */
    public function load(string $outputDir): bool
    {
        $this->outputDir = $outputDir;
        $metaPath = $outputDir . '/' . self::BUILD_META_FILE;

        if (!file_exists($metaPath)) {
            return false;
        }

        $json = file_get_contents($metaPath);
        if ($json === false) {
            return false;
        }

        $data = json_decode($json, true);
        if (!is_array($data)) {
            return false;
        }

        // Load and validate metadata
        /** @var array<string, mixed> $metadata */
        $metadata = $data['metadata'] ?? [];
        $this->metadata = $metadata;
        if (!$this->versioning->isCacheValid($this->metadata)) {
            return false;
        }

        // Check if using sharded exports (new format)
        $exportsDir = $outputDir . '/' . self::EXPORTS_DIR;
        $isSharded = is_dir($exportsDir) && !isset($data['exports']);

        if ($isSharded) {
            // Load exports from sharded files
            $this->loadShardedExports($exportsDir);
        } else {
            // Legacy: Load exports from main file
            /** @var array<string, array<string, mixed>> $exportsData */
            $exportsData = $data['exports'] ?? [];
            foreach ($exportsData as $path => $exportData) {
                $this->exports[$path] = DocumentExports::fromArray($exportData);
            }
        }

        // Load dependencies
        /** @var array<string, mixed> $depsData */
        $depsData = $data['dependencies'] ?? [];
        $this->dependencyGraph = DependencyGraph::fromArray($depsData);

        // Load output paths
        /** @var array<string, string> $outputPaths */
        $outputPaths = $data['outputs'] ?? [];
        $this->outputPaths = $outputPaths;

        $this->loaded = true;
        $this->dirtyExports = []; // Reset dirty tracking after load
        return true;
    }

    /**
     * Load exports from sharded directory structure.
     */
    private function loadShardedExports(string $exportsDir): void
    {
        // Iterate through shard directories (2-char hash prefixes)
        $shardDirs = glob($exportsDir . '/*', GLOB_ONLYDIR);
        if ($shardDirs === false) {
            return;
        }

        foreach ($shardDirs as $shardDir) {
            $files = glob($shardDir . '/*.json');
            if ($files === false) {
                continue;
            }

            foreach ($files as $file) {
                $json = file_get_contents($file);
                if ($json === false) {
                    continue;
                }

                $data = json_decode($json, true);
                if (!is_array($data) || !isset($data['path']) || !is_string($data['path'])) {
                    continue;
                }

                /** @var string $docPath */
                $docPath = $data['path'];
                unset($data['path']); // Remove path from export data
                /** @var array<string, mixed> $exportData */
                $exportData = $data;
                $this->exports[$docPath] = DocumentExports::fromArray($exportData);
            }
        }
    }

    /**
     * Save cache to output directory.
     *
     * Uses sharded storage for exports (each document in separate file).
     * Only writes changed exports for incremental efficiency.
     *
     * @param string $outputDir The output directory
     * @param string $settingsHash Hash of current settings for invalidation
     */
    public function save(string $outputDir, string $settingsHash = ''): void
    {
        $this->outputDir = $outputDir;

        if (!is_dir($outputDir)) {
            mkdir($outputDir, 0o755, true);
        }

        // Save sharded exports (only dirty ones)
        $this->saveShardedExports($outputDir);

        // Build main metadata file (no exports - they're sharded)
        $this->metadata = $this->versioning->createMetadata($settingsHash);

        $data = [
            'metadata' => $this->metadata,
            'dependencies' => $this->dependencyGraph->toArray(),
            'outputs' => $this->outputPaths,
        ];

        file_put_contents(
            $outputDir . '/' . self::BUILD_META_FILE,
            json_encode($data, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR)
        );

        // Clear dirty tracking after successful save
        $this->dirtyExports = [];
    }

    /**
     * Save exports to sharded directory structure.
     *
     * Directory structure: _exports/<hash-prefix>/<safe-filename>.json
     * Only writes files that have been modified (tracked in dirtyExports).
     */
    private function saveShardedExports(string $outputDir): void
    {
        $exportsDir = $outputDir . '/' . self::EXPORTS_DIR;

        // On first save or full rebuild, write all exports
        $writeAll = !is_dir($exportsDir) || $this->dirtyExports === [];

        if (!is_dir($exportsDir)) {
            mkdir($exportsDir, 0o755, true);
        }

        foreach ($this->exports as $docPath => $exports) {
            // Skip unchanged exports (incremental save)
            if (!$writeAll && !isset($this->dirtyExports[$docPath])) {
                continue;
            }

            $this->writeExportFile($exportsDir, $docPath, $exports);
        }
    }

    /**
     * Write a single export file to the sharded directory.
     */
    private function writeExportFile(string $exportsDir, string $docPath, DocumentExports $exports): void
    {
        // Use hash prefix for distribution (2 chars = 256 buckets)
        $hash = md5($docPath);
        $prefix = substr($hash, 0, 2);
        $shardDir = $exportsDir . '/' . $prefix;

        if (!is_dir($shardDir)) {
            mkdir($shardDir, 0o755, true);
        }

        // Use hash as filename to handle special chars in doc paths
        $filename = $hash . '.json';
        $filePath = $shardDir . '/' . $filename;

        // Include path in the data for loading
        $data = $exports->toArray();
        $data['path'] = $docPath;

        file_put_contents(
            $filePath,
            json_encode($data, JSON_THROW_ON_ERROR)
        );
    }

    /**
     * Get the shard file path for a document.
     */
    private function getExportFilePath(string $outputDir, string $docPath): string
    {
        $hash = md5($docPath);
        $prefix = substr($hash, 0, 2);
        return $outputDir . '/' . self::EXPORTS_DIR . '/' . $prefix . '/' . $hash . '.json';
    }

    /**
     * Get exports for a document.
     */
    public function getExports(string $docPath): ?DocumentExports
    {
        return $this->exports[$docPath] ?? null;
    }

    /**
     * Set exports for a document.
     * Marks the export as dirty for incremental save.
     */
    public function setExports(string $docPath, DocumentExports $exports): void
    {
        $this->exports[$docPath] = $exports;
        $this->dirtyExports[$docPath] = true;
    }

    /**
     * Get all cached exports.
     *
     * @return array<string, DocumentExports>
     */
    public function getAllExports(): array
    {
        return $this->exports;
    }

    /**
     * Get all cached document paths.
     *
     * @return string[]
     */
    public function getAllDocPaths(): array
    {
        return array_keys($this->exports);
    }

    /**
     * Get the dependency graph.
     */
    public function getDependencyGraph(): DependencyGraph
    {
        return $this->dependencyGraph;
    }

    /**
     * Set output path for a document.
     */
    public function setOutputPath(string $docPath, string $outputPath): void
    {
        $this->outputPaths[$docPath] = $outputPath;
    }

    /**
     * Get output path for a document.
     */
    public function getOutputPath(string $docPath): ?string
    {
        return $this->outputPaths[$docPath] ?? null;
    }

    /**
     * Remove a document from all cache structures.
     * Also deletes the sharded export file if it exists.
     */
    public function removeDocument(string $docPath): void
    {
        unset($this->exports[$docPath]);
        unset($this->outputPaths[$docPath]);
        unset($this->dirtyExports[$docPath]);
        $this->dependencyGraph->removeDocument($docPath);

        // Delete sharded export file if output directory is known
        if ($this->outputDir !== null) {
            $exportFile = $this->getExportFilePath($this->outputDir, $docPath);
            if (file_exists($exportFile)) {
                unlink($exportFile);
            }
        }
    }

    /**
     * Check if cache was loaded from disk.
     */
    public function isLoaded(): bool
    {
        return $this->loaded;
    }

    /**
     * Get cached settings hash.
     */
    public function getSettingsHash(): ?string
    {
        return $this->metadata['settingsHash'] ?? null;
    }

    /**
     * Clear all cached data.
     */
    public function clear(): void
    {
        $this->exports = [];
        $this->dirtyExports = [];
        $this->dependencyGraph = new DependencyGraph();
        $this->outputPaths = [];
        $this->metadata = [];
        $this->loaded = false;
    }

    /**
     * Get cache statistics.
     *
     * @return array<string, mixed>
     */
    public function getStats(): array
    {
        return [
            'documents' => count($this->exports),
            'outputs' => count($this->outputPaths),
            'graph' => $this->dependencyGraph->getStats(),
            'loaded' => $this->loaded,
        ];
    }

    /**
     * Extract cache state for serialization (used in parallel compilation).
     *
     * @return array<string, mixed>
     */
    public function extractState(): array
    {
        $exportsData = [];
        foreach ($this->exports as $path => $exports) {
            $exportsData[$path] = $exports->toArray();
        }

        return [
            'exports' => $exportsData,
            'dependencies' => $this->dependencyGraph->toArray(),
            'outputPaths' => $this->outputPaths,
        ];
    }

    /**
     * Merge state from another cache instance (used after parallel compilation).
     *
     * @param array{exports?: array<string, array<string, mixed>>, dependencies?: array<string, mixed>, outputPaths?: array<string, string>} $state State from extractState()
     */
    public function mergeState(array $state): void
    {
        // Merge exports
        $exportsData = $state['exports'] ?? [];
        foreach ($exportsData as $path => $exportData) {
            // Only add if not already present (first write wins)
            if (!isset($this->exports[$path])) {
                $this->exports[$path] = DocumentExports::fromArray($exportData);
            }
        }

        // Merge dependency graph
        $depsData = $state['dependencies'] ?? [];
        if ($depsData !== []) {
            $childGraph = DependencyGraph::fromArray($depsData);
            $this->dependencyGraph->merge($childGraph);
        }

        // Merge output paths
        $outputPaths = $state['outputPaths'] ?? [];
        foreach ($outputPaths as $docPath => $outputPath) {
            if (!isset($this->outputPaths[$docPath])) {
                $this->outputPaths[$docPath] = $outputPath;
            }
        }
    }

    /**
     * Set the input directory for file path resolution.
     */
    public function setInputDir(string $inputDir): void
    {
        $this->inputDir = $inputDir;
    }

    /**
     * Get the input directory for file path resolution.
     */
    public function getInputDir(): string
    {
        return $this->inputDir;
    }
}
