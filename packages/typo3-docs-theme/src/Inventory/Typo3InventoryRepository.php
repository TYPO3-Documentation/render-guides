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
     * @see https://regex101.com/r/aKvAce/1
     */
    private const EXTENSION_INTERLINK_REGEX = '/^ext-([^\-]*)-(.*)$/';

    /** @var array<string, Inventory>  */
    private array $inventories = [];
    /** @var list<string>  */
    private array $ignoredInventories = [];

    /** @param array<int, array<string, string>> $inventoryConfigs */
    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly AnchorReducer $anchorReducer,
        // We have to use the specific implementation as the interface does not expose the needed methods
        private readonly DefaultInventoryLoader $inventoryLoader,
        private readonly JsonLoader $jsonLoader,
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
        if (isset($this->inventories[$reducedKey])) {
            return true;
        }
        if (in_array($reducedKey, $this->ignoredInventories, true)) {
            return false;
        }
        if (!str_starts_with($reducedKey, 'ext-')) {
            return false;
        }
        if (!preg_match(self::EXTENSION_INTERLINK_REGEX, $reducedKey, $matches)) {
            return false;
        }
        $composerName = $matches[1] . '/' . $matches[2];
        if ($this->loadInventoryFromComposerExtension($reducedKey, $composerName)) {
            return true;
        }
        /* todo: Hack: Try composer name with underscores. Changes in the guides are needed
        *  So that we can receive the exact composer name from the link
        */
        if (str_contains($composerName, '-')) {
            $composerName2 = str_replace('-', '_', $composerName);
            if ($this->loadInventoryFromComposerExtension($reducedKey, $composerName2)) {
                return true;
            }
        }
        $this->logger->warning(sprintf('Interlink inventory for extension %s not found.', $composerName));
        $this->ignoredInventories[] = $reducedKey;
        return false;
    }

    private function loadInventoryFromComposerExtension(string $reducedKey, string $composerName): bool
    {
        try {
            $inventoryUrl = 'https://docs.typo3.org/p/' . $composerName . '/main/en-us/';
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
