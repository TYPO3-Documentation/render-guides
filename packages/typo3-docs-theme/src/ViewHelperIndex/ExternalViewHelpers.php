<?php

declare(strict_types=1);

namespace T3Docs\Typo3DocsTheme\ViewHelperIndex;

use phpDocumentor\Guides\ReferenceResolvers\Interlink\JsonLoader;
use Psr\Log\LoggerInterface;
use T3Docs\Typo3DocsTheme\Inventory\Typo3InventoryRepository;
use T3Docs\Typo3DocsTheme\Renderer\ViewHelpersJsonRenderer;
use T3Docs\Typo3DocsTheme\Settings\Typo3DocsThemeSettings;
use T3Docs\VersionHandling\DefaultInventories;
use Throwable;

use function is_array;
use function is_string;
use function sprintf;

/**
 * The ViewHelpers the Fluid ViewHelper Reference documents, for a ":fluid:"
 * role in any other manual.
 *
 * Read from the reference's "viewhelpers.json", in the version the manual's
 * own interlinks to "t3viewhelper" go to. @see ViewHelpersJsonRenderer
 *
 * Loaded on the first ":fluid:" that names a ViewHelper, so a manual that
 * never does makes no request. Offline, or before the reference was rendered
 * with a theme that writes the file, a ":fluid:" role renders as it did
 * before: as Fluid code.
 *
 * @phpstan-type ViewHelper array{summary: string, url: string}
 */
final class ExternalViewHelpers
{
    /** @var array<string, ViewHelper>|null */
    private ?array $viewHelpers = null;

    public function __construct(
        private readonly Typo3InventoryRepository $inventoryRepository,
        private readonly JsonLoader $jsonLoader,
        private readonly Typo3DocsThemeSettings $themeSettings,
        private readonly LoggerInterface $logger,
    ) {}

    /**
     * The ViewHelper a template calls by this name, such as "f:format.html",
     * or null for one the reference does not document.
     *
     * @return ViewHelper|null
     */
    public function find(string $name): ?array
    {
        return ($this->viewHelpers ??= $this->fetch())[$name] ?? null;
    }

    /**
     * Use these ViewHelpers instead of fetching any, as if they had been read
     * from $baseUrl. An empty list switches the lookup off.
     *
     * @param array<mixed> $json the content of a "viewhelpers.json"
     */
    public function useDefinitions(array $json, string $baseUrl): void
    {
        $this->viewHelpers = $this->read($json, $baseUrl);
    }

    /** @return array<string, ViewHelper> */
    private function fetch(): array
    {
        $key = DefaultInventories::t3viewhelper->value;
        if ($this->themeSettings->getSettings('interlink_shortcode') === $key) {
            // The reference itself: its ViewHelpers are on its own pages.
            return [];
        }

        $baseUrl = $this->inventoryRepository->getBaseUrl($key);
        if ($baseUrl === null) {
            return [];
        }

        try {
            $json = $this->jsonLoader->loadJsonFromUrl($baseUrl . ViewHelpersJsonRenderer::FILE_NAME);
        } catch (Throwable $exception) {
            $this->logger->info(sprintf('No ViewHelpers from %s: %s', $baseUrl . ViewHelpersJsonRenderer::FILE_NAME, $exception->getMessage()));
            return [];
        }

        return $this->read($json, $baseUrl);
    }

    /**
     * @param array<mixed> $json
     * @return array<string, ViewHelper>
     */
    private function read(array $json, string $baseUrl): array
    {
        $entries = $json['viewhelpers'] ?? null;
        if (!is_array($entries)) {
            return [];
        }

        $viewHelpers = [];
        foreach ($entries as $name => $entry) {
            if (!is_string($name) || !is_array($entry)) {
                continue;
            }
            $path = $entry['path'] ?? null;
            $anchor = $entry['anchor'] ?? null;
            if (!is_string($path) || $path === '' || !is_string($anchor)) {
                continue;
            }
            $summary = $entry['summary'] ?? '';
            $viewHelpers[$name] = [
                'summary' => is_string($summary) ? $summary : '',
                // The page in the version this manual links to, which is the
                // base the file was read from.
                'url' => $baseUrl . $path . '.html' . ($anchor === '' ? '' : '#' . $anchor),
            ];
        }

        return $viewHelpers;
    }
}
