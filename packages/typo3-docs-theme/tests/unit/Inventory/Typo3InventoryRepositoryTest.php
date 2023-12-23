<?php

use phpDocumentor\Guides\Interlink\InventoryLoader;
use phpDocumentor\Guides\ReferenceResolvers\SluggerAnchorReducer;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use T3Docs\Typo3DocsTheme\Inventory\Typo3InventoryRepository;
use T3Docs\Typo3DocsTheme\Settings\Typo3DocsThemeSettings;

final class Typo3InventoryRepositoryTest extends TestCase
{
    private Typo3InventoryRepository $subject;
    private Typo3DocsThemeSettings $settings;

    /** @var array<int, array<string, string>> $inventoryConfigs */
    private array $inventoryConfigs;

    protected function setUp(): void
    {
        $this->settings = new Typo3DocsThemeSettings(
            [
            ]
        );
        $this->inventoryConfigs = [
        ];
        $this->subject = $this->getInventoryRepository($this->settings, $this->inventoryConfigs);
    }

    #[Test]
    #[DataProvider('providerForInventoryKeysWithVersions')]
    public function versionInventoriesAreAdded(string $inventoryKey, bool $expected): void
    {
        self::assertEquals($this->subject->hasInventory($inventoryKey), $expected);
    }
    public static function providerForInventoryKeysWithVersions(): \Generator
    {
        yield "preferred" => [
            'inventoryKey' => 't3coreapi',
            'expected' => true,
        ];
        yield "stable" => [
          'inventoryKey' => 't3coreapi-stable',
            'expected' => true,
        ];
        yield "oldstable" => [
            'inventoryKey' => 't3coreapi-oldstable',
            'expected' => true,
        ];
        yield "dev" => [
            'inventoryKey' => 't3coreapi-dev',
            'expected' => true,
        ];
        yield "whatever" => [
            'inventoryKey' => 't3coreapi-whatever',
            'expected' => false,
        ];
        yield "preferred-non-versioned" => [
            'inventoryKey' => 'h2document',
            'expected' => true,
        ];
        yield "stable-non-versioned" => [
            'inventoryKey' => 'h2document-stable',
            'expected' => false,
        ];
        yield "whatever-non-versioned" => [
            'inventoryKey' => 't3coreapi-whatever',
            'expected' => false,
        ];
    }

    #[Test]
    #[DataProvider('providerForInventoryKeysWithVersionsAndUrl')]
    public function versionInventoryCreatesVersionedUrl(string $inventoryKey, string $expected): void
    {
        self::assertEquals($this->subject->getInventory($inventoryKey)->getBaseUrl(), $expected);
    }

    public static function providerForInventoryKeysWithVersionsAndUrl(): \Generator
    {
        yield "preferred" => [
            'inventoryKey' => 't3coreapi',
            'expected' => "https://docs.typo3.org/m/typo3/reference-coreapi/12.4/en-us/",
        ];
        yield "stable" => [
            'inventoryKey' => 't3coreapi-stable',
            'expected' => "https://docs.typo3.org/m/typo3/reference-coreapi/12.4/en-us/",
        ];
        yield "oldstable" => [
            'inventoryKey' => 't3coreapi-oldstable',
            'expected' => "https://docs.typo3.org/m/typo3/reference-coreapi/11.5/en-us/",
        ];
        yield "dev" => [
            'inventoryKey' => 't3coreapi-dev',
            'expected' => "https://docs.typo3.org/m/typo3/reference-coreapi/main/en-us/",
        ];
        yield "preferred-non-versioned" => [
            'inventoryKey' => 'h2document',
            'expected' => "https://docs.typo3.org/m/typo3/docs-how-to-document/main/en-us/",
        ];
    }

    private function getInventoryRepository(Typo3DocsThemeSettings $settings, array $inventoryConfigs)
    {
        return new Typo3InventoryRepository(
            new SluggerAnchorReducer(),
            $this->createMock(InventoryLoader::class),
            $settings,
            $inventoryConfigs,
        );
    }
}
