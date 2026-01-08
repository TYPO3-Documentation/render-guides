<?php

declare(strict_types=1);

namespace T3Docs\Typo3DocsTheme\Parser;

use phpDocumentor\Guides\Event\PostParseDocument;
use phpDocumentor\Guides\Handlers\ParseFileCommand;
use phpDocumentor\Guides\Handlers\ParseFileHandler;
use phpDocumentor\Guides\Nodes\DocumentNode;
use phpDocumentor\Guides\Nodes\ProjectNode;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\LoggerInterface;

use function assert;
use function file_exists;
use function file_get_contents;
use function file_put_contents;
use function hash;
use function is_dir;
use function ltrim;
use function mkdir;
use function serialize;
use function spl_object_hash;
use function sprintf;
use function time;
use function trim;
use function unserialize;

/**
 * Caching decorator for ParseFileHandler that caches parsed DocumentNode ASTs.
 *
 * This dramatically improves warm-cache render times by avoiding repeated parsing
 * of unchanged RST/Markdown files. The cache key includes:
 * - File path and content hash
 * - Project settings (title, version, release, copyright)
 * - Filesystem instance identity (for test isolation)
 *
 * Important: PostParseDocument events are still dispatched on cache hits to ensure
 * event listeners (like OriginalFileNameSetter) can still modify the document.
 */
final class CachingParseFileHandler
{
    private const string CACHE_DIR = 'typo3-guides-ast-cache';
    private const int DEFAULT_TTL = 86400; // 24 hours

    private readonly string $cacheBasePath;

    public function __construct(
        private readonly ParseFileHandler $inner,
        private readonly EventDispatcherInterface $eventDispatcher,
        private readonly LoggerInterface $logger,
        string $cacheDir = '',
        private readonly int $ttl = self::DEFAULT_TTL,
    ) {
        $this->cacheBasePath = $cacheDir !== ''
            ? $cacheDir
            : sys_get_temp_dir() . '/' . self::CACHE_DIR;
    }

    public function handle(ParseFileCommand $command): DocumentNode|null
    {
        $filePath = $this->buildPathOnFileSystem(
            $command->getFile(),
            $command->getDirectory(),
            $command->getExtension(),
        );

        $cacheKey = $this->computeCacheKey($command, $filePath);
        $cachePath = $this->getCachePath($cacheKey);

        // Try to load from cache
        $cachedDocument = $this->loadFromCache($cachePath, $filePath);
        if ($cachedDocument !== null) {
            $this->logger->debug(sprintf('AST cache hit for %s', $filePath));

            // Dispatch PostParseDocument event even on cache hit
            // This ensures event listeners (like OriginalFileNameSetter) still run
            $event = $this->eventDispatcher->dispatch(
                new PostParseDocument($command->getFile(), $cachedDocument, $filePath),
            );
            assert($event instanceof PostParseDocument);

            return $event->getDocumentNode();
        }

        $this->logger->debug(sprintf('AST cache miss for %s', $filePath));

        // Parse the document via the inner handler
        $document = $this->inner->handle($command);

        // Cache the result (before event modifications - we'll replay events on cache hit)
        if ($document !== null) {
            // Note: We cache the document as returned by inner handler.
            // The inner handler already dispatched PostParseDocument, so we get
            // the fully processed document. On cache hit, we dispatch again,
            // but OriginalFileNameSetter uses withKeepExistingOptions which
            // won't overwrite if already set.
            $this->saveToCache($cachePath, $document);
        }

        return $document;
    }

    private function computeCacheKey(ParseFileCommand $command, string $filePath): string
    {
        $origin = $command->getOrigin();

        // Read file contents for hashing
        $contents = '';
        if ($origin->has($filePath)) {
            $contents = $origin->read($filePath);
            if ($contents === false) {
                $contents = '';
            }
        }

        // Include ProjectNode settings in cache key
        // Different project settings = different cache entry
        $projectHash = $this->computeProjectNodeHash($command->getProjectNode());

        // Cache key includes: file path, content hash, header level, extension, project settings
        $keyData = sprintf(
            '%s|%s|%d|%s|%s',
            $filePath,
            hash('xxh3', $contents),
            $command->getInitialHeaderLevel(),
            $command->getExtension(),
            $projectHash,
        );

        // Include filesystem identity for test isolation only
        // In production, we want to share cache across runs for performance
        // In tests, each test creates its own filesystem instance and needs isolation
        if (isset($_ENV['CI_PHPUNIT'])) {
            $keyData .= '|' . spl_object_hash($origin);
        }

        return hash('xxh3', $keyData);
    }

    /**
     * Compute a hash of ProjectNode settings that affect document rendering.
     */
    private function computeProjectNodeHash(ProjectNode $projectNode): string
    {
        // Include project metadata that affects output
        $projectData = sprintf(
            '%s|%s|%s|%s',
            $projectNode->getTitle() ?? '',
            $projectNode->getVersion() ?? '',
            $projectNode->getRelease() ?? '',
            $projectNode->getCopyright() ?? '',
        );

        return hash('xxh3', $projectData);
    }

    private function getCachePath(string $cacheKey): string
    {
        // Use subdirectory based on first 2 chars of hash to avoid too many files in one dir
        $subDir = substr($cacheKey, 0, 2);

        return sprintf('%s/%s/%s.cache', $this->cacheBasePath, $subDir, $cacheKey);
    }

    private function loadFromCache(string $cachePath, string $filePath): DocumentNode|null
    {
        if (!file_exists($cachePath)) {
            return null;
        }

        $cacheData = file_get_contents($cachePath);
        if ($cacheData === false) {
            return null;
        }

        // Check TTL via file modification time
        $mtime = filemtime($cachePath);
        if ($mtime === false || (time() - $mtime) > $this->ttl) {
            $this->logger->debug(sprintf('AST cache expired for %s', $filePath));

            return null;
        }

        try {
            $document = unserialize($cacheData);
            if (!$document instanceof DocumentNode) {
                return null;
            }

            return $document;
        } catch (\Throwable $e) {
            $this->logger->warning(sprintf(
                'Failed to deserialize cached AST for %s: %s',
                $filePath,
                $e->getMessage(),
            ));

            return null;
        }
    }

    private function saveToCache(string $cachePath, DocumentNode $document): void
    {
        $cacheDir = dirname($cachePath);
        if (!is_dir($cacheDir)) {
            mkdir($cacheDir, 0o755, true);
        }

        try {
            $serialized = serialize($document);
            file_put_contents($cachePath, $serialized);
        } catch (\Throwable $e) {
            $this->logger->warning(sprintf(
                'Failed to cache AST: %s',
                $e->getMessage(),
            ));
        }
    }

    private function buildPathOnFileSystem(string $file, string $currentDirectory, string $extension): string
    {
        return ltrim(sprintf('%s/%s.%s', trim($currentDirectory, '/'), $file, $extension), '/');
    }
}
