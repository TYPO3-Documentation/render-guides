<?php

declare(strict_types=1);

namespace T3Docs\Typo3DocsTheme\Tests\Unit\Sitemap;

use phpDocumentor\Guides\ReferenceResolvers\Interlink\DefaultInventoryLoader;
use phpDocumentor\Guides\ReferenceResolvers\Interlink\JsonLoader;
use phpDocumentor\Guides\ReferenceResolvers\SluggerAnchorNormalizer;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use T3Docs\Typo3DocsTheme\Inventory\DefaultInterlinkParser;
use T3Docs\Typo3DocsTheme\Inventory\DefaultInventoryUrlBuilder;
use T3Docs\Typo3DocsTheme\Inventory\Typo3InventoryRepository;
use T3Docs\Typo3DocsTheme\Inventory\Typo3VersionService;
use T3Docs\Typo3DocsTheme\Permalinks\Permalinks;
use T3Docs\Typo3DocsTheme\Settings\Typo3DocsThemeSettings;
use T3Docs\Typo3DocsTheme\Sitemap\ManualAddress;

/**
 * Where a manual is published decides whether it gets a sitemap at all, and
 * a fixture cannot show a file that is not written.
 */
#[CoversClass(ManualAddress::class)]
final class ManualAddressTest extends TestCase
{
    #[DataProvider('manuals')]
    public function testFindsWhereAnOfficialManualIsPublished(string $shortcode, ?string $version, ?string $expected): void
    {
        self::assertSame($expected, $this->subject($shortcode)->of($version));
    }

    /** @return iterable<string, array{string, string|null, string|null}> */
    public static function manuals(): iterable
    {
        yield 'a reference in a version' => ['t3tca', '13.4', 'https://docs.typo3.org/m/typo3/reference-tca/13.4/en-us/'];
        yield 'a reference in development' => ['t3tca', 'main (development)', 'https://docs.typo3.org/m/typo3/reference-tca/main/en-us/'];
        yield 'not where project-home says' => ['t3viewhelper', '13.4', 'https://docs.typo3.org/other/typo3/view-helper-reference/13.4/en-us/'];
        yield 'a system extension' => ['typo3/cms-form', '14.3', 'https://docs.typo3.org/c/typo3/cms-form/14.3/en-us/'];
        yield 'the changelog, in main only' => ['changelog', '14.3', 'https://docs.typo3.org/c/typo3/cms-core/main/en-us/'];
        yield 'an extension of another vendor' => ['georgringer/news', '12.0', null];
        yield 'no shortcode' => ['', '13.4', null];
    }

    private function subject(string $shortcode): ManualAddress
    {
        $anchorNormalizer = new SluggerAnchorNormalizer();
        $settings = new Typo3DocsThemeSettings(['interlink_shortcode' => $shortcode]);
        $versionService = new Typo3VersionService($settings);
        $jsonLoader = $this->createMock(JsonLoader::class);

        return new ManualAddress(
            new Typo3InventoryRepository(
                new NullLogger(),
                $anchorNormalizer,
                new DefaultInventoryLoader(new NullLogger(), $jsonLoader, $anchorNormalizer),
                $jsonLoader,
                $versionService,
                [],
                new DefaultInterlinkParser($anchorNormalizer),
                new DefaultInventoryUrlBuilder($versionService),
            ),
            new Permalinks($settings),
        );
    }
}
