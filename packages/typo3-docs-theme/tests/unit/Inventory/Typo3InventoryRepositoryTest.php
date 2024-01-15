<?php

use phpDocumentor\Guides\Nodes\Inline\ReferenceNode;
use phpDocumentor\Guides\ReferenceResolvers\AnchorNormalizer;
use phpDocumentor\Guides\ReferenceResolvers\Interlink\DefaultInventoryLoader;
use phpDocumentor\Guides\ReferenceResolvers\Interlink\JsonLoader;
use phpDocumentor\Guides\ReferenceResolvers\Messages;
use phpDocumentor\Guides\ReferenceResolvers\SluggerAnchorNormalizer;
use phpDocumentor\Guides\RenderContext;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use T3Docs\Typo3DocsTheme\Inventory\Typo3InventoryRepository;
use T3Docs\Typo3DocsTheme\Settings\Typo3DocsThemeSettings;

final class Typo3InventoryRepositoryTest extends TestCase
{
    private Typo3InventoryRepository $subject;
    private Typo3DocsThemeSettings $settings;
    private JsonLoader&MockObject $jsonLoaderMock;
    private AnchorNormalizer $anchorNormalizer;
    private RenderContext&MockObject $renderContext;

    /** @var array<int, array<string, string>> $inventoryConfigs */
    private array $inventoryConfigs;

    protected function setUp(): void
    {
        $this->anchorNormalizer = new SluggerAnchorNormalizer();
        $this->settings = new Typo3DocsThemeSettings(
            [
            ]
        );
        $this->inventoryConfigs = [
        ];
        $this->jsonLoaderMock =  $this->createMock(JsonLoader::class);
        $this->subject = $this->getInventoryRepository($this->settings, $this->inventoryConfigs);
        $this->renderContext = $this->createMock(RenderContext::class);
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
        $messages = new Messages();
        $node = new ReferenceNode('someReference', '', $inventoryKey);
        self::assertTrue($this->subject->hasInventory($inventoryKey));
        self::assertEquals($expected, $this->subject->getInventory($node, $this->renderContext, $messages)->getBaseUrl());
        self::assertCount(0, $messages->getWarnings());
    }

    public static function providerForInventoryKeysWithVersionsAndUrl(): \Generator
    {
        yield "preferred" => [
            'inventoryKey' => 't3coreapi',
            'expected' => "https://docs.typo3.org/m/typo3/reference-coreapi/12.4/en-us/",
        ];
        yield "stable" => [
            'inventoryKey' => 't3coreapi/stable',
            'expected' => "https://docs.typo3.org/m/typo3/reference-coreapi/12.4/en-us/",
        ];
        yield "oldstable" => [
            'inventoryKey' => 't3coreapi/oldstable',
            'expected' => "https://docs.typo3.org/m/typo3/reference-coreapi/11.5/en-us/",
        ];
        yield "dev" => [
            'inventoryKey' => 't3coreapi/dev',
            'expected' => "https://docs.typo3.org/m/typo3/reference-coreapi/main/en-us/",
        ];
        yield "v12" => [
            'inventoryKey' => 't3coreapi/v12',
            'expected' => "https://docs.typo3.org/m/typo3/reference-coreapi/12.4/en-us/",
        ];
        yield "12.4" => [
            'inventoryKey' => 't3coreapi/12.4',
            'expected' => "https://docs.typo3.org/m/typo3/reference-coreapi/12.4/en-us/",
        ];
        yield "preferred-non-versioned" => [
            'inventoryKey' => 'h2document',
            'expected' => "https://docs.typo3.org/m/typo3/docs-how-to-document/main/en-us/",
        ];
    }

    #[Test]
    #[DataProvider('providerForExtensionInventoryUrl')]
    public function extensionInventoryUrl(string $inventoryKey, string $expected): void
    {
        $messages = new Messages();
        $node = new ReferenceNode('someReference', '', $inventoryKey);
        self::assertTrue($this->subject->hasInventory($inventoryKey));
        self::assertEquals($expected, $this->subject->getInventory($node, $this->renderContext, $messages)->getBaseUrl());
        self::assertCount(0, $messages->getWarnings());
    }

    public static function providerForExtensionInventoryUrl(): \Generator
    {
        yield "extension-without-dash" => [
            'inventoryKey' => 'georgringer/news',
            'expected' => "https://docs.typo3.org/p/georgringer/news/main/en-us/",
        ];
        yield "extension-with-dash" => [
            'inventoryKey' => 'sjbr/static-info-tables',
            'expected' => "https://docs.typo3.org/p/sjbr/static-info-tables/main/en-us/",
        ];
        yield "extension-main" => [
            'inventoryKey' => 'georgringer/news/main',
            'expected' => "https://docs.typo3.org/p/georgringer/news/main/en-us/",
        ];
        yield "extension-minor" => [
            'inventoryKey' => 'georgringer/news/3.1',
            'expected' => "https://docs.typo3.org/p/georgringer/news/3.1/en-us/",
        ];
        yield "extension-bugfix" => [
            'inventoryKey' => 'georgringer/news/3.1.0',
            'expected' => "https://docs.typo3.org/p/georgringer/news/3.1/en-us/",
        ];
        yield "sys-extension" => [
            'inventoryKey' => 'typo3/cms-adminpanel',
            'expected' => "https://docs.typo3.org/c/typo3/cms-adminpanel/12.4/en-us/",
        ];
        yield "sys-extension-major" => [
            'inventoryKey' => 'typo3/cms-adminpanel/11',
            'expected' => "https://docs.typo3.org/c/typo3/cms-adminpanel/11.5/en-us/",
        ];
        yield "sys-extension-minor" => [
            'inventoryKey' => 'typo3/cms-adminpanel/12.4',
            'expected' => "https://docs.typo3.org/c/typo3/cms-adminpanel/12.4/en-us/",
        ];
        yield "sys-extension-main" => [
            'inventoryKey' => 'typo3/cms-adminpanel/main',
            'expected' => "https://docs.typo3.org/c/typo3/cms-adminpanel/main/en-us/",
        ];
        yield "sys-extension-stable" => [
            'inventoryKey' => 'typo3/cms-adminpanel/stable',
            'expected' => "https://docs.typo3.org/c/typo3/cms-adminpanel/12.4/en-us/",
        ];
        yield "sys-extension-oldstable" => [
            'inventoryKey' => 'typo3/cms-adminpanel/oldstable',
            'expected' => "https://docs.typo3.org/c/typo3/cms-adminpanel/11.5/en-us/",
        ];
        yield "changelog always main" => [
            'inventoryKey' => 'typo3/cms-core',
            'expected' => "https://docs.typo3.org/c/typo3/cms-core/main/en-us/",
        ];
        yield "system extension legacy format" => [
            'inventoryKey' => 'ext_rte_ckeditor',
            'expected' => "https://docs.typo3.org/c/typo3/cms-rte-ckeditor/12.4/en-us/",
        ];
        yield "system extension v8" => [
            'inventoryKey' => 'typo3/cms-rte-ckeditor/v8',
            'expected' => "https://docs.typo3.org/c/typo3/cms-rte-ckeditor/8.7/en-us/",
        ];
    }

    #[Test]
    #[DataProvider('providerForExtensionNameScheme')]
    public function extensionNameScheme(string $inventoryKey, bool $expected): void
    {
        self::assertEquals($this->subject->hasInventory($inventoryKey), $expected);
    }
    public static function providerForExtensionNameScheme(): \Generator
    {
        yield "extension-without-dash" => [
            'inventoryKey' => 'georgringer/news',
            'expected' => true,
        ];
        yield "extension-with-dash" => [
            'inventoryKey' => 'sjbr/static-info-tables',
            'expected' => true,
        ];
        yield "extension-without-vendor" => [
            'inventoryKey' => 'news',
            'expected' => false,
        ];
    }


    private function getInventoryRepository(Typo3DocsThemeSettings $settings, array $inventoryConfigs)
    {
        return new Typo3InventoryRepository(
            new NullLogger(),
            $this->anchorNormalizer,
            new DefaultInventoryLoader(new NullLogger(), $this->jsonLoaderMock, $this->anchorNormalizer),
            $this->jsonLoaderMock,
            $settings,
            $inventoryConfigs,
        );
    }
}
