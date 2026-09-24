<?php

declare(strict_types=1);

namespace T3Docs\Typo3DocsTheme\ReferenceResolvers\ObjectsInventory;

use phpDocumentor\Guides\ReferenceResolvers\Interlink\JsonLoader;
use Psr\Log\LoggerInterface;
use T3Docs\Typo3DocsTheme\Inventory\Typo3InventoryRepository;
use T3Docs\Typo3DocsTheme\Settings\Typo3DocsThemeSettings;
use T3Docs\VersionHandling\DefaultInventories;
use Throwable;

use function is_array;
use function is_string;
use function sprintf;

/**
 * The files TYPO3 Explained defines, for a ":file:" in any other manual.
 *
 * The official manuals define their files in one place: every
 * ".. typo3:file::" lives in TYPO3 Explained. Another manual that names such
 * a file would otherwise render it as plain code, without the link and without
 * the popup that tells a reader where the file lives in Composer and Classic
 * mode installations. TYPO3 Explained publishes its definitions in
 * "files.json" (@see \T3Docs\Typo3DocsTheme\Renderer\FilesJsonRenderer), and
 * this reads them from the version the manual's own interlinks to "t3coreapi"
 * go to.
 *
 * Loaded on the first ":file:" that finds no definition in the manual itself,
 * so a manual that never needs it makes no request. A manual rendered while
 * the file cannot be fetched -- offline, or before TYPO3 Explained was
 * rendered with a theme that writes it -- renders those files as plain code,
 * the way it did before.
 */
final class ExternalFileObjects
{
    public const FILE_NAME = 'files.json';

    /** @var list<FileObject>|null */
    private ?array $fileObjects = null;

    public function __construct(
        private readonly Typo3InventoryRepository $inventoryRepository,
        private readonly JsonLoader $jsonLoader,
        private readonly Typo3DocsThemeSettings $themeSettings,
        private readonly LoggerInterface $logger,
    ) {}

    /** @return list<FileObject> */
    public function all(): array
    {
        return $this->fileObjects ??= $this->fetch();
    }

    /**
     * Use these definitions instead of fetching any, as if they had been read
     * from $baseUrl. An empty list switches the lookup off.
     *
     * @param array<mixed> $json the content of a "files.json"
     */
    public function useDefinitions(array $json, string $baseUrl): void
    {
        $this->fileObjects = $this->read($json, $baseUrl);
    }

    /** @return list<FileObject> */
    private function fetch(): array
    {
        $key = DefaultInventories::t3coreapi->value;
        if ($this->themeSettings->getSettings('interlink_shortcode') === $key) {
            // TYPO3 Explained itself: its definitions are all local.
            return [];
        }

        $baseUrl = $this->inventoryRepository->getBaseUrl($key);
        if ($baseUrl === null) {
            return [];
        }

        try {
            $json = $this->jsonLoader->loadJsonFromUrl($baseUrl . self::FILE_NAME);
        } catch (Throwable $exception) {
            $this->logger->info(sprintf('No file definitions from %s: %s', $baseUrl . self::FILE_NAME, $exception->getMessage()));
            return [];
        }

        return $this->read($json, $baseUrl);
    }

    /**
     * @param array<mixed> $json
     * @return list<FileObject>
     */
    private function read(array $json, string $baseUrl): array
    {
        $files = $json['files'] ?? null;
        if (!is_array($files)) {
            return [];
        }

        $fileObjects = [];
        foreach ($files as $entry) {
            if (!is_array($entry) || !is_string($entry['path'] ?? null) || $entry['path'] === '') {
                continue;
            }
            $fileObject = FileObject::fromArray($entry, $baseUrl . $entry['path']);
            if ($fileObject !== null) {
                $fileObjects[] = $fileObject;
            }
        }

        return $fileObjects;
    }
}
