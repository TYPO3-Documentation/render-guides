<?php

use phpDocumentor\Guides\ReferenceResolvers\SluggerAnchorNormalizer;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use T3Docs\Typo3DocsTheme\Inventory\DefaultInterlinkParser;
use T3Docs\Typo3DocsTheme\Inventory\InterlinkParts;

final class DefaultInterlinkParserTest extends TestCase
{
    private DefaultInterlinkParser $parser;

    protected function setUp(): void
    {
        $this->parser = new DefaultInterlinkParser(new SluggerAnchorNormalizer());
    }

    #[Test]
    #[DataProvider('vendorPackageProvider')]
    public function parsesVendorPackageForms(string $key, string $kind, string $vendor, string $package, ?string $version): void
    {
        $parts = $this->parser->parse($key);
        self::assertInstanceOf(InterlinkParts::class, $parts);
        self::assertSame($kind, $parts->kind);
        self::assertSame($vendor, $parts->vendor);
        self::assertSame($package, $parts->package);
        self::assertSame($version, $parts->version);
        self::assertNotEmpty($parts->normalizedKey);
    }

    public static function vendorPackageProvider(): \Generator
    {
        // core
        yield 'core no version' => ['typo3/cms-adminpanel', 'core', 'typo3', 'cms-adminpanel', null];
        yield 'core with version' => ['typo3/cms-adminpanel/13.4', 'core', 'typo3', 'cms-adminpanel', '13.4'];

        // generic package
        yield 'package' => ['georgringer/news', 'package', 'georgringer', 'news', null];
        yield 'package main' => ['georgringer/news/main', 'package', 'georgringer', 'news', 'main'];
        yield 'package @12.4' => ['georgringer/news@12.4', 'package', 'georgringer', 'news', '12.4'];

        // default inventories
        yield 'default inventory' => ['t3coreapi', 'default', 'typo3', 't3coreapi', null];
        yield 'default inventory with explicit version' => ['t3coreapi/12.4', 'default', 'typo3', 't3coreapi', '12.4'];
        yield 'default inventory with @stable' => ['t3coreapi@stable', 'default', 'typo3', 't3coreapi', 'stable'];
        yield 'default inventory with @main' => ['t3coreapi@main', 'default', 'typo3', 't3coreapi', 'main'];
        yield 'default inventory with @12.4' => ['t3coreapi@12.4', 'default', 'typo3', 't3coreapi', '12.4'];
    }

    #[Test]
    public function parsesLegacyExtFormat(): void
    {
        $parts = $this->parser->parse('ext_rte_ckeditor');
        self::assertSame('legacy-ext', $parts->kind);
        self::assertSame('typo3', $parts->vendor);
        self::assertSame('cms-rte-ckeditor', $parts->package);
        self::assertNull($parts->version);
    }

    #[Test]
    public function appliesFallbackWhenFirstNonAlnumBecomesSlash(): void
    {
        // Example: "georgringer-news" -> fallback to "georgringer/news"
        $parts = $this->parser->parse('georgringer-news');
        self::assertSame('package', $parts->kind);
        self::assertSame('georgringer', $parts->vendor);
        self::assertSame('news', $parts->package);
        self::assertNull($parts->version);
    }

    #[Test]
    public function returnsNullForUnparseableKeys(): void
    {
        self::assertNull($this->parser->parse('totally invalid key with spaces'));
    }
}
