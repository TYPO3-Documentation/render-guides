<?php

declare(strict_types=1);

namespace T3Docs\GuidesExtension\Compiler\Cache;

/**
 * Detects which source files have changed since the last build.
 *
 * Uses timestamp-first checking for performance:
 * 1. If mtime unchanged → file unchanged (fast path)
 * 2. If mtime changed → compute hash to verify actual content change
 */
final class ChangeDetector
{
    /** @var int Number of files checked via fast path (mtime only) */
    private int $fastPathHits = 0;

    /** @var int Number of files requiring hash computation */
    private int $hashComputations = 0;

    public function __construct(
        private readonly ContentHasher $hasher,
    ) {}

    /**
     * Compare current document paths against cached state with a file path resolver.
     *
     * @param string[] $documentPaths Document paths (without extension)
     * @param array<string, DocumentExports> $cachedExports Previous build's exports
     * @param callable(string): string $fileResolver Resolves document path to actual file path
     * @return ChangeDetectionResult
     */
    public function detectChangesWithResolver(array $documentPaths, array $cachedExports, callable $fileResolver): ChangeDetectionResult
    {
        $dirty = [];
        $clean = [];
        $new = [];
        $this->fastPathHits = 0;
        $this->hashComputations = 0;

        foreach ($documentPaths as $docPath) {
            // Resolve to actual file path
            $filePath = $fileResolver($docPath);
            $cached = $cachedExports[$docPath] ?? null;

            if ($cached === null) {
                // New file - no cached data
                $new[] = $docPath;
                continue;
            }

            // Timestamp-first optimization
            $currentMtime = $this->getFileMtime($filePath);

            if ($currentMtime === $cached->lastModified && $cached->lastModified > 0) {
                // Fast path: timestamp unchanged, assume content unchanged
                $this->fastPathHits++;
                $clean[] = $docPath;
                continue;
            }

            // Timestamp changed - verify with content hash
            $this->hashComputations++;
            $currentHash = $this->hasher->hashFile($filePath);

            if ($currentHash === $cached->contentHash) {
                // Content same despite mtime change (git checkout, touch, etc.)
                $clean[] = $docPath;
            } else {
                // Content actually changed
                $dirty[] = $docPath;
            }
        }

        // Detect deleted files
        $deleted = [];
        $currentSet = array_flip($documentPaths);
        foreach (array_keys($cachedExports) as $cachedPath) {
            if (!isset($currentSet[$cachedPath])) {
                $deleted[] = $cachedPath;
            }
        }

        return new ChangeDetectionResult($dirty, $clean, $new, $deleted);
    }

    /**
     * Compare current source files against cached state (legacy method).
     *
     * @param string[] $sourceFiles Current source file paths
     * @param array<string, DocumentExports> $cachedExports Previous build's exports
     * @return ChangeDetectionResult
     */
    public function detectChanges(array $sourceFiles, array $cachedExports): ChangeDetectionResult
    {
        return $this->detectChangesWithResolver($sourceFiles, $cachedExports, fn($path) => $path);
    }

    /**
     * Quick check if a single file has changed using timestamp-first approach.
     */
    public function hasFileChanged(string $filePath, ?DocumentExports $cached): bool
    {
        if ($cached === null) {
            return true;
        }

        // Timestamp-first check
        $currentMtime = $this->getFileMtime($filePath);
        if ($currentMtime === $cached->lastModified && $cached->lastModified > 0) {
            return false;
        }

        // Verify with hash
        $currentHash = $this->hasher->hashFile($filePath);
        return $currentHash !== $cached->contentHash;
    }

    /**
     * Get file modification time.
     */
    public function getFileMtime(string $filePath): int
    {
        if (!file_exists($filePath)) {
            return 0;
        }

        $mtime = filemtime($filePath);
        return $mtime !== false ? $mtime : 0;
    }

    /**
     * Get performance statistics for last detection run.
     *
     * @return array{fastPathHits: int, hashComputations: int}
     */
    public function getStats(): array
    {
        return [
            'fastPathHits' => $this->fastPathHits,
            'hashComputations' => $this->hashComputations,
        ];
    }
}
