<?php

declare(strict_types=1);

namespace T3Docs\Typo3DocsTheme\Inventory;

use phpDocumentor\Guides\ReferenceResolvers\Interlink\JsonLoader;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

use function dirname;
use function file_exists;
use function file_get_contents;
use function file_put_contents;
use function glob;
use function hash;
use function is_array;
use function is_dir;
use function is_int;
use function json_decode;
use function json_encode;
use function mkdir;
use function sprintf;
use function sys_get_temp_dir;
use function time;
use function unlink;

use const JSON_THROW_ON_ERROR;

/**
 * Caching decorator for JsonLoader that stores inventory files locally.
 *
 * This significantly reduces render time by avoiding repeated HTTP requests
 * for inventory files that change infrequently.
 */
final class CachingJsonLoader extends JsonLoader
{
    private const int DEFAULT_TTL = 3600; // 1 hour

    public function __construct(
        HttpClientInterface $client,
        private readonly JsonLoader $inner,
        private readonly LoggerInterface $logger,
        private readonly string $cacheDir = '',
        private readonly int $ttl = self::DEFAULT_TTL,
    ) {
        parent::__construct($client);
    }

    /** @return array<mixed> */
    #[\Override]
    public function loadJsonFromUrl(string $url): array
    {
        $cacheFile = $this->getCacheFilePath($url);

        // Try to load from cache
        $cached = $this->loadFromCache($cacheFile);
        if ($cached !== null) {
            $this->logger->debug(sprintf('Inventory cache HIT: %s', $url));
            return $cached;
        }

        $this->logger->debug(sprintf('Inventory cache MISS: %s', $url));

        // Fetch from network via the decorated loader
        $data = $this->inner->loadJsonFromUrl($url);

        // Store in cache
        $this->saveToCache($cacheFile, $data);

        return $data;
    }

    /** @return array<mixed> */
    #[\Override]
    public function loadJsonFromString(string $jsonString, string $url = ''): array
    {
        // No caching for string loading - delegate directly
        return $this->inner->loadJsonFromString($jsonString, $url);
    }

    private function getCacheFilePath(string $url): string
    {
        $cacheDir = $this->cacheDir !== '' ? $this->cacheDir : $this->getDefaultCacheDir();
        $hash = hash('xxh128', $url);

        return $cacheDir . '/' . $hash . '.json';
    }

    private function getDefaultCacheDir(): string
    {
        // Use system temp directory with a subdirectory for our cache
        $tmpDir = sys_get_temp_dir();

        return $tmpDir . '/typo3-guides-inventory-cache';
    }

    /**
     * @return array<mixed>|null
     */
    private function loadFromCache(string $cacheFile): ?array
    {
        if (!file_exists($cacheFile)) {
            return null;
        }

        $content = file_get_contents($cacheFile);
        if ($content === false) {
            return null;
        }

        try {
            $cached = json_decode($content, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            // Invalid cache file, remove it
            @unlink($cacheFile);
            return null;
        }

        if (!is_array($cached)) {
            return null;
        }

        // Check TTL
        $timestamp = $cached['_cache_timestamp'] ?? 0;
        if (!is_int($timestamp) || (time() - $timestamp) > $this->ttl) {
            // Cache expired
            return null;
        }

        // Return the actual data without metadata
        $data = $cached['_cache_data'] ?? null;

        return is_array($data) ? $data : null;
    }

    /**
     * @param array<mixed> $data
     */
    private function saveToCache(string $cacheFile, array $data): void
    {
        $cacheDir = dirname($cacheFile);

        // Ensure cache directory exists
        if (!is_dir($cacheDir)) {
            if (!@mkdir($cacheDir, 0o755, true) && !is_dir($cacheDir)) {
                $this->logger->warning(sprintf('Failed to create inventory cache directory: %s', $cacheDir));
                return;
            }
        }

        $cacheData = [
            '_cache_timestamp' => time(),
            '_cache_data' => $data,
        ];

        try {
            $json = json_encode($cacheData, JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            $this->logger->warning(sprintf('Failed to encode inventory cache data: %s', $e->getMessage()));
            return;
        }

        if (file_put_contents($cacheFile, $json) === false) {
            $this->logger->warning(sprintf('Failed to write inventory cache file: %s', $cacheFile));
        }
    }

    /**
     * Clear all cached inventory files.
     */
    public function clearCache(): void
    {
        $cacheDir = $this->cacheDir !== '' ? $this->cacheDir : $this->getDefaultCacheDir();

        if (!is_dir($cacheDir)) {
            return;
        }

        $files = glob($cacheDir . '/*.json');
        if ($files === false) {
            return;
        }

        foreach ($files as $file) {
            @unlink($file);
        }
    }
}
