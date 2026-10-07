<?php

declare(strict_types=1);

namespace T3Docs\Typo3DocsTheme\TypoScriptReference;

use phpDocumentor\Guides\ReferenceResolvers\AnchorNormalizer;
use phpDocumentor\Guides\ReferenceResolvers\Interlink\JsonLoader;
use Psr\Log\LoggerInterface;
use T3Docs\Typo3DocsTheme\Inventory\Typo3InventoryRepository;
use T3Docs\Typo3DocsTheme\Renderer\ConfvalIndexJsonRenderer;
use T3Docs\Typo3DocsTheme\Settings\Typo3DocsThemeSettings;
use T3Docs\VersionHandling\DefaultInventories;
use Throwable;

use function in_array;
use function is_array;
use function is_string;
use function sprintf;

/**
 * The options and object types the TypoScript reference documents, for a
 * ":typoscript:" or ":tsconfig:" role in any other manual. The reference
 * documents TSconfig too, since TYPO3 v12.
 *
 * Options are read from the reference's "confvals.json", object types from
 * its "objects.inv.json", in the version the manual's own interlinks to
 * "t3tsref" go to. @see ConfvalIndexJsonRenderer, ObjectTypes
 *
 * Each file is loaded on the first role that needs it, so a manual that never
 * names an option or an object type makes no request. Offline, or before the
 * reference was rendered with a theme that writes "confvals.json", a role
 * renders as it did before: as TypoScript code.
 *
 * @phpstan-type Option array{name: string, type: string, summary: string, url: string}
 * @phpstan-import-type ObjectType from ObjectTypes
 */
final class ExternalTypoScript
{
    /** The kinds of option a ":typoscript:" or ":tsconfig:" role can name. */
    public const SEARCH_FACETS = ['TypoScript', 'TSconfig'];

    private ?Options $options = null;

    /** @var array<string, Options> the options of any other manual a role names one of, by its interlink key */
    private array $manuals = [];

    /** @var array<string, bool> whether the options of a manual could be read */
    private array $loaded = [];

    /** @var array<string, ObjectType>|null by anchor */
    private ?array $labels = null;

    /** @var array<string, list<string>> */
    private array $pagesByTitle = [];

    private ?string $baseUrl = null;

    public function __construct(
        private readonly Typo3InventoryRepository $inventoryRepository,
        private readonly JsonLoader $jsonLoader,
        private readonly Typo3DocsThemeSettings $themeSettings,
        private readonly AnchorNormalizer $anchorNormalizer,
        private readonly LoggerInterface $logger,
    ) {}

    /**
     * The option at this full path, such as "stdWrap.parseFunc", or null for
     * one the reference does not document.
     *
     * @return Option|null
     */
    public function findOption(string $path): ?array
    {
        $this->options ??= $this->readTypoScriptReference();

        return $this->options->find($path, $this->anchorNormalizer);
    }

    /**
     * What the reference documents as a word of its own, such as the object
     * type "USER" or the function "stdWrap", or null. @see Options::findWord()
     *
     * @return Option|null
     */
    public function findWord(string $word): ?array
    {
        $this->options ??= $this->readTypoScriptReference();

        return $this->options->findWord($word, $this->anchorNormalizer);
    }

    /**
     * The option a role names by the name of its confval, in angle brackets:
     * "module.tx_extbase <t3coreapi:some-key>", in the manual of that interlink
     * key. Any kind of option counts: the author chose it.
     *
     * Whether the manual's options could be read comes with it, so that a name
     * it does not document is told apart from a manual that was not reached.
     *
     * @return array{bool, Option|null}
     */
    public function findNamed(string $manual, string $name): array
    {
        if ($manual === DefaultInventories::t3tsref->value) {
            $this->options ??= $this->readTypoScriptReference();
            $options = $this->options;
        } else {
            $options = $this->manuals[$manual] ??= $this->readManual($manual);
        }

        return [$this->loaded[$manual] ?? false, $options->named($name, $this->anchorNormalizer)];
    }

    /**
     * Where the reference documents this object type, such as "PAGEVIEW", or
     * null.
     *
     * @return ObjectType|null
     */
    public function findObjectType(string $type): ?array
    {
        if ($this->labels === null) {
            $this->readInventory($this->fetch('objects.inv.json'));
        }

        return ObjectTypes::find(
            $type,
            $this->anchorNormalizer->reduceAnchor($type),
            fn(string $anchor): ?array => $this->labels[$anchor] ?? null,
            $this->pagesByTitle,
        );
    }

    /**
     * Use these files instead of fetching any, as if they had been read from
     * $baseUrl. Empty ones switch the lookup off.
     *
     * @param array<mixed> $confvals the content of a "confvals.json"
     * @param array<mixed> $inventory the content of an "objects.inv.json"
     */
    public function useDefinitions(array $confvals, array $inventory, string $baseUrl): void
    {
        $this->baseUrl = $baseUrl;
        $this->options = $this->readOptions($confvals, $baseUrl);
        $this->loaded[DefaultInventories::t3tsref->value] = $confvals !== [];
        $this->readInventory($inventory);
    }

    /**
     * Use these options for the manual of an interlink key instead of fetching
     * them, as if they had been read from $baseUrl.
     *
     * @param array<mixed> $confvals the content of a "confvals.json"
     */
    public function useConfvalsOf(string $manual, array $confvals, string $baseUrl): void
    {
        $this->manuals[$manual] = $this->readOptions($confvals, $baseUrl);
        $this->loaded[$manual] = $confvals !== [];
    }

    private function readTypoScriptReference(): Options
    {
        $json = $this->fetch(ConfvalIndexJsonRenderer::FILE_NAME);
        $this->loaded[DefaultInventories::t3tsref->value] = $json !== [];

        return $this->readOptions($json, $this->baseUrl);
    }

    /** The options of another manual, in the version this manual's interlinks to it go to. */
    private function readManual(string $manual): Options
    {
        $baseUrl = $this->inventoryRepository->getBaseUrl($manual);
        $json = [];
        if ($baseUrl !== null) {
            try {
                $json = $this->jsonLoader->loadJsonFromUrl($baseUrl . ConfvalIndexJsonRenderer::FILE_NAME);
            } catch (Throwable $exception) {
                $this->logger->info(sprintf('No options from %s: %s', $baseUrl . ConfvalIndexJsonRenderer::FILE_NAME, $exception->getMessage()));
            }
        }
        $this->loaded[$manual] = $json !== [];

        return $this->readOptions($json, $baseUrl);
    }

    /** @return array<mixed> */
    private function fetch(string $fileName): array
    {
        $key = DefaultInventories::t3tsref->value;
        if ($this->themeSettings->getSettings('interlink_shortcode') === $key) {
            // The reference itself: its options are on its own pages.
            return [];
        }

        $this->baseUrl ??= $this->inventoryRepository->getBaseUrl($key);
        if ($this->baseUrl === null) {
            return [];
        }

        try {
            return $this->jsonLoader->loadJsonFromUrl($this->baseUrl . $fileName);
        } catch (Throwable $exception) {
            $this->logger->info(sprintf('No TypoScript from %s: %s', $this->baseUrl . $fileName, $exception->getMessage()));
            return [];
        }
    }

    /** @param array<mixed> $json */
    private function readOptions(array $json, ?string $baseUrl): Options
    {
        $options = new Options();
        $entries = $json['confvals'] ?? null;
        if (!is_array($entries) || $baseUrl === null) {
            return $options;
        }

        foreach ($entries as $anchor => $entry) {
            if (!is_string($anchor) || !is_array($entry)) {
                continue;
            }
            $path = $entry['path'] ?? null;
            if (!is_string($path) || $path === '') {
                continue;
            }
            $name = $entry['name'] ?? '';
            $type = $entry['type'] ?? '';
            $summary = $entry['summary'] ?? '';
            $fields = $entry['fields'] ?? [];
            $options->add($anchor, [
                'name' => is_string($name) ? $name : '',
                'type' => is_string($type) ? $type : '',
                'summary' => is_string($summary) ? $summary : '',
                // The page in the version this manual links to, which is the
                // base the file was read from.
                'url' => $baseUrl . $path . '.html#' . $anchor,
            ], OptionPaths::declaredIn(is_array($fields) ? $fields : []), in_array($entry['searchFacet'] ?? null, self::SEARCH_FACETS, true));
        }

        return $options;
    }

    /** @param array<mixed> $json */
    private function readInventory(array $json): void
    {
        $this->labels = [];
        $this->pagesByTitle = [];
        if ($this->baseUrl === null) {
            return;
        }

        foreach ($this->entries($json, 'std:label') as $anchor => [$url, $title]) {
            $this->labels[$anchor] = ['title' => $title, 'url' => $this->baseUrl . $url];
        }
        foreach ($this->entries($json, 'std:doc') as [$url, $title]) {
            $this->pagesByTitle[$title][] = $this->baseUrl . $url;
        }
    }

    /**
     * The address and title of each entry of a group, by its key. An entry of
     * "objects.inv.json" is [project, version, address, title].
     *
     * @param array<mixed> $json
     * @return array<string, array{string, string}>
     */
    private function entries(array $json, string $group): array
    {
        $entries = $json[$group] ?? null;
        if (!is_array($entries)) {
            return [];
        }

        $read = [];
        foreach ($entries as $key => $entry) {
            if (!is_string($key) || !is_array($entry) || !is_string($entry[2] ?? null) || !is_string($entry[3] ?? null)) {
                continue;
            }
            $read[$key] = [$entry[2], $entry[3]];
        }

        return $read;
    }
}
