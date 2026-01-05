<?php

declare(strict_types=1);

namespace T3Docs\GuidesExtension\Compiler\Cache;

/**
 * Central cache for incremental build state.
 *
 * All cache data is stored in a single _build_meta.json file in the output directory.
 * This ensures the cache travels with the rendered output and stays in sync.
 *
 * Stores:
 * - Document exports (anchors, titles, citations, content hash, mtime)
 * - Dependency graph (which doc imports from which)
 * - Output paths (where rendered files are stored)
 * - Metadata (version, timestamps, settings hash)
 */
final class IncrementalBuildCache
{
    private const BUILD_META_FILE = '_build_meta.json';

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

    public function __construct(
        private readonly CacheVersioning $versioning,
    ) {
        $this->dependencyGraph = new DependencyGraph();
    }

    /**
     * Load cache from output directory.
     *
     * @param string $outputDir The output directory where _build_meta.json is stored
     * @return bool True if cache was loaded and is valid
     */
    public function load(string $outputDir): bool
    {
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

        // Load exports
        /** @var array<string, array<string, mixed>> $exportsData */
        $exportsData = $data['exports'] ?? [];
        foreach ($exportsData as $path => $exportData) {
            $this->exports[$path] = DocumentExports::fromArray($exportData);
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
        return true;
    }

    /**
     * Save cache to output directory as _build_meta.json.
     *
     * @param string $outputDir The output directory
     * @param string $settingsHash Hash of current settings for invalidation
     */
    public function save(string $outputDir, string $settingsHash = ''): void
    {
        if (!is_dir($outputDir)) {
            mkdir($outputDir, 0o755, true);
        }

        // Build exports data
        $exportsData = [];
        foreach ($this->exports as $path => $exports) {
            $exportsData[$path] = $exports->toArray();
        }

        // Build complete cache structure
        $this->metadata = $this->versioning->createMetadata($settingsHash);

        $data = [
            'metadata' => $this->metadata,
            'exports' => $exportsData,
            'dependencies' => $this->dependencyGraph->toArray(),
            'outputs' => $this->outputPaths,
        ];

        file_put_contents(
            $outputDir . '/' . self::BUILD_META_FILE,
            json_encode($data, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR)
        );
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
     */
    public function setExports(string $docPath, DocumentExports $exports): void
    {
        $this->exports[$docPath] = $exports;
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
     */
    public function removeDocument(string $docPath): void
    {
        unset($this->exports[$docPath]);
        unset($this->outputPaths[$docPath]);
        $this->dependencyGraph->removeDocument($docPath);
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
