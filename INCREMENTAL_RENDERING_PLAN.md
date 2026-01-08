# Incremental Rendering Implementation Plan

## Overview

Implement incremental/partial document rendering for phpdocumentor/guides to avoid re-rendering unchanged documents while correctly handling cross-document dependencies.

## Architecture

```
┌─────────────────────────────────────────────────────────────────────────┐
│                        INCREMENTAL RENDERING FLOW                       │
├─────────────────────────────────────────────────────────────────────────┤
│                                                                         │
│  1. PARSE PHASE                                                         │
│     ┌─────────────┐                                                     │
│     │ Load Cache  │ → DocumentExportsCache (previous build state)       │
│     └─────────────┘                                                     │
│            ↓                                                            │
│     ┌─────────────┐                                                     │
│     │ Parse Files │ → Compute content hash per document                 │
│     └─────────────┘                                                     │
│            ↓                                                            │
│     ┌─────────────┐                                                     │
│     │ Detect      │ → Compare hashes, build dirty set                   │
│     │ Changes     │                                                     │
│     └─────────────┘                                                     │
│                                                                         │
│  2. COMPILE PHASE                                                       │
│     ┌─────────────┐                                                     │
│     │ Collect     │ → Extract exports (anchors, titles) per doc         │
│     │ Exports     │                                                     │
│     └─────────────┘                                                     │
│            ↓                                                            │
│     ┌─────────────┐                                                     │
│     │ Build Deps  │ → Track which docs import from which                │
│     │ Graph       │                                                     │
│     └─────────────┘                                                     │
│            ↓                                                            │
│     ┌─────────────┐                                                     │
│     │ Propagate   │ → If exports changed, mark dependents dirty         │
│     │ Dirty       │                                                     │
│     └─────────────┘                                                     │
│                                                                         │
│  3. RENDER PHASE                                                        │
│     ┌─────────────┐                                                     │
│     │ Render Only │ → Skip clean documents, render dirty only           │
│     │ Dirty Docs  │                                                     │
│     └─────────────┘                                                     │
│            ↓                                                            │
│     ┌─────────────┐                                                     │
│     │ Save Cache  │ → Persist exports, hashes, dependencies             │
│     └─────────────┘                                                     │
│                                                                         │
└─────────────────────────────────────────────────────────────────────────┘
```

## Phase 1: Data Structures

### 1.1 DocumentExports (new class)

```php
// packages/typo3-guides-extension/src/Compiler/Cache/DocumentExports.php

final class DocumentExports
{
    public function __construct(
        public readonly string $documentPath,
        public readonly string $contentHash,      // Hash of source content
        public readonly string $exportsHash,      // Hash of exports only
        public readonly array $anchors,           // ['anchor-name' => 'Title', ...]
        public readonly array $titles,            // Section titles
        public readonly array $citations,         // Citation names
        public readonly int $lastModified,        // Timestamp
    ) {}

    public function hasExportsChanged(self $other): bool
    {
        return $this->exportsHash !== $other->exportsHash;
    }
}
```

### 1.2 DependencyGraph (new class)

```php
// packages/typo3-guides-extension/src/Compiler/Cache/DependencyGraph.php

final class DependencyGraph
{
    /** @var array<string, string[]> docPath -> [imported docPaths] */
    private array $imports = [];

    /** @var array<string, string[]> docPath -> [dependent docPaths] */
    private array $dependents = [];

    public function addImport(string $fromDoc, string $toDoc): void;
    public function getImports(string $docPath): array;
    public function getDependents(string $docPath): array;
    public function propagateDirty(array $dirtyDocs): array;
}
```

### 1.3 IncrementalBuildCache (new class)

```php
// packages/typo3-guides-extension/src/Compiler/Cache/IncrementalBuildCache.php

final class IncrementalBuildCache
{
    /** @var array<string, DocumentExports> */
    private array $exports = [];

    private DependencyGraph $dependencyGraph;

    /** @var array<string, string> docPath -> rendered HTML path */
    private array $outputPaths = [];

    public function load(string $cacheDir): void;
    public function save(string $cacheDir): void;
    public function getExports(string $docPath): ?DocumentExports;
    public function setExports(string $docPath, DocumentExports $exports): void;
    public function getDependencyGraph(): DependencyGraph;
    public function computeDirtySet(array $changedDocs): array;
}
```

## Phase 2: Compiler Extensions

### 2.1 ExportsCollectorPass (new compiler pass)

**Priority:** 4500 (after CollectLinkTargetsTransformer at 5000)

```php
// packages/typo3-guides-extension/src/Compiler/ExportsCollectorPass.php

final class ExportsCollectorPass implements CompilerPass
{
    public function __construct(
        private IncrementalBuildCache $cache,
    ) {}

    public function getPriority(): int
    {
        return 4500; // After link targets collected
    }

    public function run(array $documents, CompilerContextInterface $context): array
    {
        foreach ($documents as $document) {
            $exports = $this->collectExports($document, $context->getProjectNode());
            $this->cache->setExports($document->getFilePath(), $exports);
        }
        return $documents;
    }

    private function collectExports(DocumentNode $doc, ProjectNode $project): DocumentExports
    {
        // Extract all anchors, titles, citations from this document
        // Compute content hash and exports hash
    }
}
```

### 2.2 DependencyGraphPass (new compiler pass)

**Priority:** 4000 (after exports collected)

```php
// packages/typo3-guides-extension/src/Compiler/DependencyGraphPass.php

final class DependencyGraphPass implements CompilerPass
{
    public function __construct(
        private IncrementalBuildCache $cache,
    ) {}

    public function getPriority(): int
    {
        return 4000;
    }

    public function run(array $documents, CompilerContextInterface $context): array
    {
        $graph = $this->cache->getDependencyGraph();

        foreach ($documents as $document) {
            $imports = $this->findImports($document, $context->getProjectNode());
            foreach ($imports as $importedDocPath) {
                $graph->addImport($document->getFilePath(), $importedDocPath);
            }
        }

        return $documents;
    }

    private function findImports(DocumentNode $doc, ProjectNode $project): array
    {
        // Traverse document tree
        // Find all CrossReferenceNode, DocReferenceNode, etc.
        // Map each reference to its source document
    }
}
```

## Phase 3: Change Detection

### 3.1 ContentHasher (new utility)

```php
// packages/typo3-guides-extension/src/Compiler/Cache/ContentHasher.php

final class ContentHasher
{
    public function hashFile(string $filePath): string
    {
        return hash_file('xxh3', $filePath);
    }

    public function hashExports(array $anchors, array $titles, array $citations): string
    {
        $data = serialize([$anchors, $titles, $citations]);
        return hash('xxh3', $data);
    }
}
```

### 3.2 ChangeDetector (new class)

```php
// packages/typo3-guides-extension/src/Compiler/Cache/ChangeDetector.php

final class ChangeDetector
{
    public function __construct(
        private IncrementalBuildCache $cache,
        private ContentHasher $hasher,
    ) {}

    public function detectChanges(array $sourceFiles): ChangeDetectionResult
    {
        $dirty = [];
        $clean = [];
        $new = [];
        $deleted = [];

        foreach ($sourceFiles as $filePath) {
            $currentHash = $this->hasher->hashFile($filePath);
            $cached = $this->cache->getExports($filePath);

            if ($cached === null) {
                $new[] = $filePath;
            } elseif ($cached->contentHash !== $currentHash) {
                $dirty[] = $filePath;
            } else {
                $clean[] = $filePath;
            }
        }

        // Detect deleted files
        foreach ($this->cache->getAllDocPaths() as $cachedPath) {
            if (!in_array($cachedPath, $sourceFiles, true)) {
                $deleted[] = $cachedPath;
            }
        }

        return new ChangeDetectionResult($dirty, $clean, $new, $deleted);
    }
}
```

## Phase 4: Dirty Propagation

### 4.1 DirtyPropagator (new class)

```php
// packages/typo3-guides-extension/src/Compiler/Cache/DirtyPropagator.php

final class DirtyPropagator
{
    public function __construct(
        private IncrementalBuildCache $cache,
    ) {}

    public function propagate(ChangeDetectionResult $changes): PropagationResult
    {
        $dirty = array_merge($changes->dirty, $changes->new);

        // For deleted files, all their dependents become dirty
        foreach ($changes->deleted as $deletedPath) {
            $dependents = $this->cache->getDependencyGraph()->getDependents($deletedPath);
            $dirty = array_merge($dirty, $dependents);
        }

        // Check if exports changed for dirty docs
        // If so, propagate to dependents
        $propagated = [];
        foreach ($dirty as $dirtyPath) {
            $oldExports = $this->cache->getExports($dirtyPath);
            // After recompile, compare exports
            // If changed, add dependents to dirty
        }

        return new PropagationResult(
            documentsToRender: array_unique($dirty),
            documentsToSkip: $changes->clean,
        );
    }
}
```

## Phase 5: Special Cases

### 5.1 Global Invalidation Triggers

Certain changes invalidate ALL documents:

1. **Toctree structure changes** - Navigation affects all pages
2. **Theme changes** - All pages need re-rendering
3. **Global settings** - Configuration affects all pages
4. **Interlink inventory changes** - External references

```php
final class GlobalInvalidationDetector
{
    public function requiresFullRebuild(
        ChangeDetectionResult $changes,
        IncrementalBuildCache $cache,
    ): bool {
        // Check if any toctree-related file changed
        // Check if settings/configuration changed
        // Check if theme files changed
        return false;
    }
}
```

### 5.2 Menu/Navigation Handling

```php
// Menus are built from document hierarchy
// If hierarchy changes, ALL documents need menu re-rendered

// Option 1: Re-render all on hierarchy change
// Option 2: Separate menu rendering pass (cache menu HTML separately)
```

## Phase 6: Integration Points

### 6.1 Modified ParseDirectoryHandler

```php
// Hook into file collection to compute hashes early
class ParseDirectoryHandler
{
    public function handle(ParseDirectoryCommand $command): array
    {
        $files = $this->collectFiles($command);

        // NEW: Detect changes
        $changes = $this->changeDetector->detectChanges($files);

        // Only parse dirty/new files
        $filesToParse = array_merge($changes->dirty, $changes->new);

        // ... parse only necessary files
    }
}
```

### 6.2 Modified RenderHandler

```php
class RenderHandler
{
    public function handle(RenderCommand $command): void
    {
        // NEW: Get dirty set
        $dirty = $this->cache->getDirtySet();

        foreach ($documents as $document) {
            if (!in_array($document->getFilePath(), $dirty, true)) {
                // Skip - use cached output
                continue;
            }

            // Render as normal
            $this->renderDocument($document);
        }
    }
}
```

## Phase 7: Cache Persistence

### 7.1 Cache File Format

```
.cache/
  incremental/
    exports.json          # All DocumentExports serialized
    dependencies.json     # DependencyGraph serialized
    metadata.json         # Build timestamp, version, settings hash
```

### 7.2 Cache Versioning

```php
final class CacheVersioning
{
    private const CACHE_VERSION = 1;

    public function isCacheValid(string $cacheDir): bool
    {
        $metadata = $this->loadMetadata($cacheDir);

        // Check cache version
        if ($metadata['version'] !== self::CACHE_VERSION) {
            return false;
        }

        // Check settings hash (if settings changed, invalidate)
        if ($metadata['settingsHash'] !== $this->computeSettingsHash()) {
            return false;
        }

        return true;
    }
}
```

## Implementation Order

1. **Phase 1: Data Structures** - Create DocumentExports, DependencyGraph, IncrementalBuildCache
2. **Phase 2: Exports Collection** - Implement ExportsCollectorPass
3. **Phase 3: Dependency Graph** - Implement DependencyGraphPass
4. **Phase 4: Change Detection** - Implement ContentHasher, ChangeDetector
5. **Phase 5: Dirty Propagation** - Implement DirtyPropagator
6. **Phase 6: Cache Persistence** - Implement save/load
7. **Phase 7: Integration** - Modify handlers to use incremental logic
8. **Phase 8: Special Cases** - Handle toctree, menus, global invalidation
9. **Phase 9: Testing & Benchmarks** - Verify correctness and performance

## Files to Create

```
packages/typo3-guides-extension/src/Compiler/Cache/
├── DocumentExports.php
├── DependencyGraph.php
├── IncrementalBuildCache.php
├── ContentHasher.php
├── ChangeDetector.php
├── ChangeDetectionResult.php
├── DirtyPropagator.php
├── PropagationResult.php
├── GlobalInvalidationDetector.php
└── CacheVersioning.php

packages/typo3-guides-extension/src/Compiler/
├── ExportsCollectorPass.php
└── DependencyGraphPass.php
```

## Configuration Options

```php
// New configuration options
'incremental' => [
    'enabled' => true,
    'cacheDir' => '.cache/incremental',
    'forceFullRebuild' => false,
    'ignorePaths' => ['_includes/*'],
],
```

## Estimated Complexity

| Component | Lines of Code | Complexity |
|-----------|---------------|------------|
| DocumentExports | ~50 | Low |
| DependencyGraph | ~100 | Medium |
| IncrementalBuildCache | ~150 | Medium |
| ExportsCollectorPass | ~200 | Medium |
| DependencyGraphPass | ~150 | Medium |
| ChangeDetector | ~100 | Medium |
| DirtyPropagator | ~150 | High |
| Cache Persistence | ~100 | Low |
| Handler Integration | ~100 | Medium |
| Special Cases | ~200 | High |
| **Total** | **~1300** | **Medium-High** |
