<?php

namespace T3Docs\Typo3DocsTheme\Inventory;

use phpDocumentor\Guides\Interlink\DefaultInventoryLoader;
use phpDocumentor\Guides\Interlink\Inventory;
use phpDocumentor\Guides\Interlink\InventoryRepository;
use phpDocumentor\Guides\Interlink\JsonLoader;
use phpDocumentor\Guides\ReferenceResolvers\AnchorReducer;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Symfony\Component\HttpClient\Exception\ClientException;
use T3Docs\Typo3DocsTheme\Settings\Typo3DocsThemeSettings;

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
        private readonly AnchorReducer          $anchorReducer,
        // We have to use the specific implementation as the interface does not expose the needed methods
        private readonly DefaultInventoryLoader $inventoryLoader,
        private readonly JsonLoader             $jsonLoader,
        private readonly Typo3DocsThemeSettings $settings,
        array                                   $inventoryConfigs,
    ) {
        foreach ($inventoryConfigs as $inventory) {
            $this->inventories[$this->anchorReducer->reduceAnchor($inventory['id'])] = new Inventory($inventory['url']);
        }
        foreach (DefaultInventories::cases() as $defaultInventory) {
            $id = $this->anchorReducer->reduceAnchor($defaultInventory->name);
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
            $version = $matches[1];
        }
        return $version;
    }

    private function addInventory(string $key, string $url, bool $overrideExisting): void
    {
        $reducedKey = $this->anchorReducer->reduceAnchor($key);
        if ($overrideExisting || !isset($this->inventories[$reducedKey])) {
            $this->inventories[$reducedKey] = new Inventory($url);
        }
    }

    public function hasInventory(string $key): bool
    {
        $reducedKey = $this->anchorReducer->reduceAnchor($key);
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
        $inventory = new Inventory($inventoryUrl);
        $this->inventoryLoader->loadInventoryFromJson($inventory, $json);
        $this->inventories[$reducedKey] = $inventory;
    }

    public function getInventory(string $key): Inventory
    {
        if (!$this->hasInventory($key)) {
            throw new RuntimeException('Inventory with key ' . $key . ' not found. ', 1_671_398_986);
        }
        $reducedKey = $this->anchorReducer->reduceAnchor($key);
        $this->inventoryLoader->loadInventory($this->inventories[$reducedKey]);

        return $this->inventories[$reducedKey];
    }
}
