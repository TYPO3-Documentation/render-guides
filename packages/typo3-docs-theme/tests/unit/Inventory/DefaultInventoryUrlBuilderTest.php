<?php

declare(strict_types=1);

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use T3Docs\Typo3DocsTheme\Inventory\DefaultInventoryUrlBuilder;
use T3Docs\Typo3DocsTheme\Inventory\InterlinkParts;
use T3Docs\Typo3DocsTheme\Inventory\Typo3VersionService;

final class DefaultInventoryUrlBuilderTest extends TestCase
{
    /** @var Typo3VersionService&MockObject */
    private $versions;

    protected function setUp(): void
    {
        $this->versions = $this->createMock(Typo3VersionService::class);
    }

    #[Test]
    public function core_without_explicit_version_uses_preferred_core_version(): void
    {
        $this->versions->method('getPreferredVersion')->willReturn('13.4');
        $this->versions->method('resolveCoreVersion')->with('13.4')->willReturn('13.4');

        $builder = new DefaultInventoryUrlBuilder($this->versions);
        $parts   = new InterlinkParts('typo3/cms-adminpanel', 'core', 'typo3', 'cms-adminpanel', null);

        self::assertSame(
            'https://docs.typo3.org/c/typo3/cms-adminpanel/13.4/en-us/',
            $builder->buildUrl($parts)
        );
    }

    #[Test]
    public function cms_core_is_always_main_even_if_version_given(): void
    {
        // Even if service would resolve to something else, cms-core must be 'main'
        $this->versions->method('resolveCoreVersion')->willReturn('12.4');

        $builder = new DefaultInventoryUrlBuilder($this->versions);
        $parts   = new InterlinkParts('typo3/cms-core', 'core', 'typo3', 'cms-core', '12.4');

        self::assertSame(
            'https://docs.typo3.org/c/typo3/cms-core/main/en-us/',
            $builder->buildUrl($parts)
        );
    }

    #[Test]
    public function legacy_ext_behaves_like_core_and_uses_preferred(): void
    {
        $this->versions->method('getPreferredVersion')->willReturn('13.4');
        $this->versions->method('resolveCoreVersion')->willReturn('13.4');

        $builder = new DefaultInventoryUrlBuilder($this->versions);
        $parts   = new InterlinkParts('ext_adminpanel', 'legacy-ext', 'typo3', 'cms-adminpanel', null);

        self::assertSame(
            'https://docs.typo3.org/c/typo3/cms-adminpanel/13.4/en-us/',
            $builder->buildUrl($parts)
        );
    }

    #[Test]
    #[DataProvider('packageVersions')]
    public function third_party_packages_use_p_namespace_and_generic_version_resolution(string $inputVersion, string $resolvedMinor, string $expectedUrl): void
    {
        $this->versions->method('resolveVersion')->with($inputVersion ?: 'main')->willReturn($resolvedMinor);

        $builder = new DefaultInventoryUrlBuilder($this->versions);
        $parts   = new InterlinkParts('georgringer/news', 'package', 'georgringer', 'news', $inputVersion ?: null);

        self::assertSame($expectedUrl, $builder->buildUrl($parts));
    }

    public static function packageVersions(): \Generator
    {
        yield 'no version → main' => [
            'inputVersion' => '',
            'resolvedMinor' => 'main',
            'expectedUrl' => 'https://docs.typo3.org/p/georgringer/news/main/en-us/',
        ];
        yield 'bugfix collapses to minor' => [
            'inputVersion' => '3.1.0',
            'resolvedMinor' => '3.1',
            'expectedUrl' => 'https://docs.typo3.org/p/georgringer/news/3.1/en-us/',
        ];
        yield 'explicit minor' => [
            'inputVersion' => '3.2',
            'resolvedMinor' => '3.2',
            'expectedUrl' => 'https://docs.typo3.org/p/georgringer/news/3.2/en-us/',
        ];
    }

    #[Test]
    public function default_inventory_versioned_example_coreapi(): void
    {
        // For default inventories the "package" is often a version token, which then gets resolveCoreVersion()
        $this->versions->method('resolveCoreVersion')->with('12.4')->willReturn('12.4');

        $builder = new DefaultInventoryUrlBuilder($this->versions);
        // kind=default, vendor='t3coreapi', package='12.4'
        $parts   = new InterlinkParts('t3coreapi/12.4', 'default', 'typo3', 't3coreapi', '12.4');

        self::assertSame(
            // Enum DefaultInventories::t3coreapi()->getUrl('12.4') → this known URL:
            'https://docs.typo3.org/m/typo3/reference-coreapi/12.4/en-us/',
            $builder->buildUrl($parts)
        );
    }

    #[Test]
    public function default_inventory_non_versioned_example_h2document_uses_main(): void
    {
        // For non-versioned default inventories the enum returns a fixed "main" URL; no resolveCoreVersion needed.
        $builder = new DefaultInventoryUrlBuilder($this->versions);
        // kind=default, vendor='h2document' (enum key), package value not used; keep anything, e.g. 'main'
        $parts   = new InterlinkParts('h2document', 'default', 'typo3', 'h2document', 'main');

        self::assertSame(
            'https://docs.typo3.org/m/typo3/docs-how-to-document/main/en-us/',
            $builder->buildUrl($parts)
        );
    }
}
