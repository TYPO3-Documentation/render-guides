<?php

declare(strict_types=1);

namespace T3Docs\GuidesExtension\Compiler\Cache;

/**
 * Handles cache versioning and validation.
 *
 * Cache is invalidated when:
 * - Cache format version changes
 * - PHP version changes (affects serialization)
 * - Package version changes
 */
final class CacheVersioning
{
    /**
     * Current cache format version.
     * Increment when cache structure changes incompatibly.
     */
    private const CACHE_VERSION = 1;

    /**
     * @param string $packageVersion Package version for additional validation
     */
    public function __construct(
        private readonly string $packageVersion = '1.0.0',
    ) {}

    /**
     * Check if cached metadata is still valid.
     *
     * @param array<string, mixed> $metadata Cached metadata
     * @return bool True if cache is valid
     */
    public function isCacheValid(array $metadata): bool
    {
        // Check cache version
        if (($metadata['version'] ?? 0) !== self::CACHE_VERSION) {
            return false;
        }

        // Check PHP major version (minor version changes are usually compatible)
        $cachedPhpVersion = $metadata['phpVersion'] ?? '';
        $currentPhpMajor = PHP_MAJOR_VERSION . '.' . PHP_MINOR_VERSION;
        if (!str_starts_with($cachedPhpVersion, $currentPhpMajor)) {
            return false;
        }

        return true;
    }

    /**
     * Create metadata for cache persistence.
     *
     * @param string $settingsHash Hash of project settings
     * @return array<string, mixed>
     */
    public function createMetadata(string $settingsHash = ''): array
    {
        return [
            'version' => self::CACHE_VERSION,
            'phpVersion' => PHP_VERSION,
            'packageVersion' => $this->packageVersion,
            'settingsHash' => $settingsHash,
            'createdAt' => time(),
        ];
    }

    /**
     * Get current cache version.
     */
    public function getCacheVersion(): int
    {
        return self::CACHE_VERSION;
    }
}
