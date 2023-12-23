<?php

namespace T3Docs\Typo3DocsTheme\Inventory;

use phpDocumentor\Guides\Interlink\Inventory;
use phpDocumentor\Guides\Interlink\InventoryLoader;
use phpDocumentor\Guides\Interlink\InventoryRepository;
use phpDocumentor\Guides\ReferenceResolvers\AnchorReducer;
use RuntimeException;
use T3Docs\Typo3DocsTheme\Settings\Typo3DocsThemeSettings;

final class Typo3InventoryRepository implements InventoryRepository
{
    /** @var array<string, Inventory>  */
    private array $inventories = [];

    /** @param array<int, array<string, string>> $inventoryConfigs */
    public function __construct(
        private readonly AnchorReducer $anchorReducer,
        private readonly InventoryLoader $inventoryLoader,
        Typo3DocsThemeSettings $settings,
        array $inventoryConfigs,
    ) {
        foreach ($inventoryConfigs as $inventory) {
            $this->inventories[$this->anchorReducer->reduceAnchor($inventory['id'])] = new Inventory($inventory['url']);
        }
        foreach (DefaultInventories::cases() as $defaultInventory) {
            $id = $this->anchorReducer->reduceAnchor($defaultInventory->name);
            $url = $defaultInventory->value;
            if (!str_contains($url, '{typo3_version}')) {
                $this->addInventory($id, $url, false);
                continue;
            }
            foreach (Typo3VersionMapping::cases() as $versionMapping) {
                $mappedUrl = str_replace('{typo3_version}', $versionMapping->getVersion(), $url);
                $this->addInventory($id . '-' . $versionMapping->value, $mappedUrl, false);
            }
            if ($settings->hasSettings('typo3_core_preferred')) {
                $preferred = $settings->getSettings('typo3_core_preferred');
                $preferred = Typo3VersionMapping::tryFrom($preferred)?->getVersion() ?? $preferred;
            } else {
                $preferred = Typo3VersionMapping::getDefault()->getVersion();
            }
            $this->addInventory($id, str_replace('{typo3_version}', $preferred, $url), false);
        }
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

        return isset($this->inventories[$reducedKey]);
    }

    public function getInventory(string $key): Inventory
    {
        $reducedKey = $this->anchorReducer->reduceAnchor($key);
        if (!$this->hasInventory($reducedKey)) {
            throw new RuntimeException('Inventory with key ' . $reducedKey . ' not found. ', 1_671_398_986);
        }

        $this->inventoryLoader->loadInventory($this->inventories[$reducedKey]);

        return $this->inventories[$reducedKey];
    }
}
