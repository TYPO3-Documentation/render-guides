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
use T3Docs\Typo3DocsTheme\Settings\Typo3DocsThemeSettings;
use T3Docs\VersionHandling\DefaultInventories;
use T3Docs\VersionHandling\Typo3VersionMapping;

final class Typo3InventoryRepository implements InventoryRepository
{
    /**
     * @see https://regex101.com/r/EXCPkt/8
     *
     * https://getcomposer.org/doc/04-schema.md#name
     */
    private const EXTENSION_INTERLINK_REGEX = '/^([^\/\s]+)\/([^\/\s]+)(\/([^\/\s]+))?$/';
    /**
     * @see https://regex101.com/r/Kx7VyS/2
     */
    private const VERSION_MINOR_REGEX = '/^(\d+\.\d+)\.\d+$/';
    /**
     * @see https://regex101.com/r/Ljhv1I/1
     */
    private const VERSION_MAJOR_REGEX = '/^(\d+)(\.\d+)?(\.\d+)?$/';

    /** @var array<string, Inventory> */
    private array $inventories = [];
    /** @var list<string> */
    private array $ignoredInventories = [];

    /** @param array<int, array<string, string>> $inventoryConfigs */
    public function __construct(
        private readonly LoggerInterface        $logger,
        private readonly AnchorNormalizer       $anchorNormalizer,
        // We have to use the specific implementation as the interface does not expose the needed methods
        private readonly DefaultInventoryLoader $inventoryLoader,
        private readonly JsonLoader             $jsonLoader,
        private readonly Typo3DocsThemeSettings $settings,
        array                                   $inventoryConfigs,
    ) {
        foreach ($inventoryConfigs as $inventory) {
            $this->inventories[$this->anchorNormalizer->reduceAnchor($inventory['id'])] = new Inventory($inventory['url'], $anchorNormalizer);
        }
        foreach (DefaultInventories::cases() as $defaultInventory) {
            $id = $this->anchorNormalizer->reduceAnchor($defaultInventory->name);
            $url = $defaultInventory->getUrl();
            if (!str_contains($url, '{typo3_version}')) {
                $this->addInventory($id, $url, false);
                continue;
            }
            foreach (Typo3VersionMapping::cases() as $versionMapping) {
                $mappedUrl = str_replace('{typo3_version}', $versionMapping->getVersion(), $url);
                $this->addInventory($id . '-' . $versionMapping->value, $mappedUrl, false);
            }
            $preferred = $this->getPreferredVersion();
            $this->addInventory($id, str_replace('{typo3_version}', $preferred, $url), false);
        }
    }

    private function getPreferredVersion(): string
    {
        if ($this->settings->hasSettings('typo3_core_preferred')) {
            $preferred = $this->settings->getSettings('typo3_core_preferred');
            return $this->resolveVersion($preferred);
        }
        return Typo3VersionMapping::getDefault()->getVersion();
    }

    private function resolveCoreVersion(string $versionName): string
    {
        $version = ltrim($versionName, 'v');
        if (preg_match(self::VERSION_MAJOR_REGEX, $version, $matches)) {
            $version = $matches[1];
        }
        $version = Typo3VersionMapping::tryFrom($version)?->getVersion() ?? $version;

        return $this->resolveVersion($version);
    }

    private function resolveVersion(string $versionName): string
    {
        $version = trim($versionName, 'v');
        if (preg_match(self::VERSION_MINOR_REGEX, $version, $matches)) {
            return $matches[1];
        }
        $mappedVersion = Typo3VersionMapping::tryFrom($version);
        if ($mappedVersion !== null) {
            return $mappedVersion->getVersion();
        }
        return $version;
    }

    private function addInventory(string $key, string $url, bool $overrideExisting): void
    {
        $reducedKey = $this->anchorNormalizer->reduceAnchor($key);
        if ($overrideExisting || !isset($this->inventories[$reducedKey])) {
            $this->inventories[$reducedKey] = new Inventory($url, $this->anchorNormalizer);
        }
    }

    public function hasInventory(string $key): bool
    {
        $reducedKey = $this->anchorNormalizer->reduceAnchor($key);
        if (isset($this->inventories[$reducedKey])) {
            return true;
        }
        if (in_array($reducedKey, $this->ignoredInventories, true)) {
            return false;
        }
        if (str_starts_with($key, 'ext_')) {
            // Legacy keys for system extensions
            // As was commonly defined in the sphinx settings, i.e. ext_adminpanel
            $extensionKey = 'cms-' . str_replace('_', '-', substr($key, 4));
            if ($this->loadInventoryFromComposerExtension($reducedKey, 'typo3', $extensionKey, null)) {
                return true;
            }
        }
        if (!preg_match(self::EXTENSION_INTERLINK_REGEX, $key, $matches)) {
            return false;
        }
        if ($this->loadInventoryFromComposerExtension($reducedKey, $matches[1], $matches[2], $matches[4] ?? null)) {
            return true;
        }
        $this->logger->warning(sprintf('Interlink inventory for manual %s not found.', $key));
        $this->ignoredInventories[] = $reducedKey;
        return false;
    }

    private function loadInventoryFromComposerExtension(string $reducedKey, string $match1, string $match2, string|null $version): bool
    {
        try {
            if ($match1 === 'typo3') {
                $version ??= $this->getPreferredVersion();
                $version = $this->resolveCoreVersion($version);
                if ($match2 === 'cms-core') {
                    $version = 'main';
                }
                $inventoryUrl = sprintf("https://docs.typo3.org/c/%s/%s/%s/en-us/", $match1, $match2, $version);
            } elseif($defaultInventory = DefaultInventories::tryFrom($match1)) {
                // we do not have a composer name here but a default inventory with a version, for example "t3coreapi/12.4"
                $version = $this->resolveCoreVersion($match2);
                $inventoryUrl = str_replace('{typo3_version}', $version, $defaultInventory->getUrl());
            } else {
                $version ??= 'main';
                $version = $this->resolveVersion($version);
                $inventoryUrl = sprintf("https://docs.typo3.org/p/%s/%s/%s/en-us/", $match1, $match2, $version);
            }
            $json = $this->jsonLoader->loadJsonFromUrl($inventoryUrl . 'objects.inv.json');
            $this->loadInventoryFromJson($inventoryUrl, $json, $reducedKey);
            return true;
        } catch (ClientException) {
            return false;
        }
    }

    /**
     * @param array<mixed> $json
     */
    private function loadInventoryFromJson(string $inventoryUrl, array $json, string $reducedKey): void
    {
        $inventory = new Inventory($inventoryUrl, $this->anchorNormalizer);
        $this->inventoryLoader->loadInventoryFromJson($inventory, $json);
        $this->inventories[$reducedKey] = $inventory;
    }

    public function getInventory(CrossReferenceNode $node, RenderContext $renderContext, Messages $messages): Inventory|null
    {
        $key = $node->getInterlinkDomain();
        $reducedKey = $this->anchorNormalizer->reduceAnchor($node->getInterlinkDomain());
        if (!$this->hasInventory($key)) {
            $messages->addWarning(
                new Message(
                    sprintf(
                        'Inventory with key %s not found. ',
                        $key,
                    ),
                    array_merge($renderContext->getLoggerInformation(), $node->getDebugInformation()),
                ),
            );

            return null;
        }

        $this->inventoryLoader->loadInventory($this->inventories[$reducedKey]);

        return $this->inventories[$reducedKey];
    }

    public function getLink(CrossReferenceNode $node, RenderContext $renderContext, Messages $messages): InventoryLink|null
    {
        $inventory = $this->getInventory($node, $renderContext, $messages);
        $group = $inventory?->getGroup($node, $renderContext, $messages);

        return $group?->getLink($node, $renderContext, $messages);
    }
}
