<?php

namespace T3Docs\Typo3DocsTheme\Inventory;

use phpDocumentor\Guides\Nodes\Inline\CrossReferenceNode;
use phpDocumentor\Guides\ReferenceResolvers\Interlink\DefaultInventoryLoader;
use phpDocumentor\Guides\ReferenceResolvers\Interlink\Inventory;
use phpDocumentor\Guides\ReferenceResolvers\Interlink\InventoryLink;
use phpDocumentor\Guides\ReferenceResolvers\Interlink\InventoryRepository;
use phpDocumentor\Guides\ReferenceResolvers\Interlink\JsonLoader;
use phpDocumentor\Guides\ReferenceResolvers\AnchorNormalizer;
use phpDocumentor\Guides\ReferenceResolvers\Message;
use phpDocumentor\Guides\ReferenceResolvers\Messages;
use phpDocumentor\Guides\RenderContext;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpClient\Exception\ClientException;
use T3Docs\VersionHandling\DefaultInventories;
use T3Docs\VersionHandling\Typo3VersionMapping;

final class Typo3InventoryRepository implements InventoryRepository
{
    /**
     * @see https://getcomposer.org/doc/04-schema.md#name
     * @see https://regex101.com/r/EXCPkt/8
     */
    public const EXTENSION_INTERLINK_REGEX = '/^([^\/\s]+)\/([^\/\s]+)(\/([^\/\s]+))?$/';

    /** @var array<string, Inventory> */
    private array $inventories = [];
    /** @var list<string> */
    private array $ignoredInventories = [];

    /**
     * @param array<int, array<string, string>> $inventoryConfigs
     */
    public function __construct(
        private readonly LoggerInterface              $logger,
        private readonly AnchorNormalizer             $anchorNormalizer,
        // We have to use the specific implementation as the interface does not expose the needed methods
        private readonly DefaultInventoryLoader       $inventoryLoader,
        private readonly JsonLoader                   $jsonLoader,
        private readonly Typo3VersionService          $typo3VersionService,
        array                                         $inventoryConfigs,
        private readonly InterlinkParserInterface     $parser,
        private readonly InventoryUrlBuilderInterface $urlBuilder,
    ) {
        foreach ($inventoryConfigs as $inventory) {
            $this->inventories[$this->anchorNormalizer->reduceAnchor($inventory['id'])]
                = new Inventory($inventory['url'], $this->anchorNormalizer);
        }

        foreach (DefaultInventories::cases() as $defaultInventory) {
            $id = $this->anchorNormalizer->reduceAnchor($defaultInventory->name);
            if (!$defaultInventory->isVersioned()) {
                $this->addInventory($id, $defaultInventory->getUrl(''), false);
                continue;
            }
            foreach (Typo3VersionMapping::cases() as $versionMapping) {
                $mappedUrl = $defaultInventory->getUrl($versionMapping->getVersion());
                $this->addInventory($id . '-' . $versionMapping->value, $mappedUrl, false);
            }
            $preferred = $this->typo3VersionService->getPreferredVersion();
            $this->addInventory($id, $defaultInventory->getUrl($preferred), false);
        }
    }

    private function addInventory(string $key, string $url, bool $overrideExisting): void
    {
        $reducedKey = $this->anchorNormalizer->reduceAnchor($key);
        if ($overrideExisting || !isset($this->inventories[$reducedKey])) {
            $this->inventories[$reducedKey] = new Inventory($url, $this->anchorNormalizer);
        }
    }

    /** Pure parsing; no network, no mutation. */
    public function parseOnly(string $key): ?InterlinkParts
    {
        return $this->parser->parse($key);
    }

    /** Pure: return the URL we *would* use; no network, no mutation. */
    public function previewUrl(string $key): ?string
    {
        $parts = $this->parser->parse($key);
        return $parts ? $this->urlBuilder->buildUrl($parts) : null;
    }

    /**
     * Lightweight key check with optional eager loading.
     * When $eagerLoad = false, this will only validate/normalize without any HTTP calls.
     */
    public function hasInventory(string $key, bool $eagerLoad = true): bool
    {
        $reducedKey = $this->anchorNormalizer->reduceAnchor($key);

        if (isset($this->inventories[$reducedKey])) {
            return true;
        }
        if (\in_array($reducedKey, $this->ignoredInventories, true)) {
            return false;
        }

        $parts = $this->parser->parse($key);
        if (!$parts) {
            return false;
        }

        if (!$eagerLoad) {
            // syntactically valid & mappable, but skip network
            return true;
        }

        $inventoryUrl = $this->urlBuilder->buildUrl($parts);
        if (!$inventoryUrl) {
            $this->ignoredInventories[] = $reducedKey;
            return false;
        }

        if ($this->tryLoadInventoryJson($reducedKey, $inventoryUrl)) {
            return ($this->inventories[$reducedKey] ?? false) instanceof Inventory
                && $this->inventories[$reducedKey]->isLoaded();
        }

        $this->logger->warning(\sprintf('Interlink inventory for manual %s not found.', $key));
        $this->ignoredInventories[] = $reducedKey;
        return false;
    }

    private function tryLoadInventoryJson(string $reducedKey, string $inventoryUrl): bool
    {
        try {
            $json = $this->jsonLoader->loadJsonFromUrl($inventoryUrl . 'objects.inv.json');
            if ($json === []) {
                return false;
            }
            /** @var array<string, mixed> $json */
            $this->loadInventoryFromJson($inventoryUrl, $json, $reducedKey);
            return true;
        } catch (ClientException) {
            return false;
        }
    }

    /**
     * @param array<string, mixed> $json
     */
    private function loadInventoryFromJson(string $inventoryUrl, array $json, string $reducedKey): void
    {
        $inventory = new Inventory($inventoryUrl, $this->anchorNormalizer);
        $this->inventoryLoader->loadInventoryFromJson($inventory, $json);
        $this->inventories[$reducedKey] = $inventory;
    }

    public function getInventory(CrossReferenceNode $node, RenderContext $renderContext, Messages $messages): ?Inventory
    {
        $key = $node->getInterlinkDomain();
        $reducedKey = $this->anchorNormalizer->reduceAnchor($key);

        if (!$this->hasInventory($key)) {
            $messages->addWarning(
                new Message(
                    \sprintf('Inventory with key %s not found. ', $key),
                    \array_merge($renderContext->getLoggerInformation(), $node->getDebugInformation()),
                ),
            );
            return null;
        }

        // Ensure fully loaded (no-op if already)
        $this->inventoryLoader->loadInventory($this->inventories[$reducedKey]);

        return $this->inventories[$reducedKey];
    }

    public function getLink(CrossReferenceNode $node, RenderContext $renderContext, Messages $messages): ?InventoryLink
    {
        $inventory = $this->getInventory($node, $renderContext, $messages);
        $group = $inventory?->getGroup($node, $renderContext, $messages);

        return $group?->getLink($node, $renderContext, $messages);
    }
}
