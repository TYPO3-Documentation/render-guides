<?php

declare(strict_types=1);

namespace T3Docs\GuidesExtension\Compiler\Cache;

/**
 * Fast content hashing utility for change detection.
 * Uses xxHash when available for speed, falls back to SHA-256.
 */
final class ContentHasher
{
    private readonly string $algorithm;

    public function __construct()
    {
        // xxh128 is fastest, fall back to sha256
        $this->algorithm = in_array('xxh128', hash_algos(), true) ? 'xxh128' : 'sha256';
    }

    /**
     * Hash a file's contents.
     */
    public function hashFile(string $filePath): string
    {
        if (!file_exists($filePath)) {
            return '';
        }

        $hash = hash_file($this->algorithm, $filePath);
        return $hash !== false ? $hash : '';
    }

    /**
     * Hash arbitrary string content.
     */
    public function hashContent(string $content): string
    {
        return hash($this->algorithm, $content);
    }

    /**
     * Compute hash of document exports for dependency invalidation.
     * Only includes the "public interface" - anchors, titles, citations, document title.
     */
    public function hashExports(array $anchors, array $sectionTitles, array $citations, string $documentTitle = ''): string
    {
        // Sort keys for deterministic hashing
        ksort($anchors);
        ksort($sectionTitles);
        sort($citations);

        $data = json_encode([
            'anchors' => $anchors,
            'sectionTitles' => $sectionTitles,
            'citations' => $citations,
            'documentTitle' => $documentTitle,
        ], JSON_THROW_ON_ERROR);

        return hash($this->algorithm, $data);
    }

    /**
     * Get the algorithm being used.
     */
    public function getAlgorithm(): string
    {
        return $this->algorithm;
    }
}
