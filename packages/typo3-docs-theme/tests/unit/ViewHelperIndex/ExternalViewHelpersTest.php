<?php

declare(strict_types=1);

namespace T3Docs\Typo3DocsTheme\Tests\Unit\ViewHelperIndex;

use phpDocumentor\Guides\ReferenceResolvers\Interlink\DefaultInventoryLoader;
use phpDocumentor\Guides\ReferenceResolvers\Interlink\JsonLoader;
use phpDocumentor\Guides\ReferenceResolvers\SluggerAnchorNormalizer;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use RuntimeException;
use T3Docs\Typo3DocsTheme\Inventory\DefaultInterlinkParser;
use T3Docs\Typo3DocsTheme\Inventory\DefaultInventoryUrlBuilder;
use T3Docs\Typo3DocsTheme\Inventory\Typo3InventoryRepository;
use T3Docs\Typo3DocsTheme\Inventory\Typo3VersionService;
use T3Docs\Typo3DocsTheme\Settings\Typo3DocsThemeSettings;
use T3Docs\Typo3DocsTheme\ViewHelperIndex\ExternalViewHelpers;

/**
 * The integration fixtures never fetch: they hand the ViewHelpers over, so
 * where they come from and what happens without them is covered here.
 */
#[CoversClass(ExternalViewHelpers::class)]
final class ExternalViewHelpersTest extends TestCase
{
    private JsonLoader&MockObject $jsonLoader;

    protected function setUp(): void
    {
        $this->jsonLoader = $this->createMock(JsonLoader::class);
    }

    public function testReadsTheViewHelpersOfTheReferenceTheManualLinksTo(): void
    {
        $this->jsonLoader->expects(self::once())
            ->method('loadJsonFromUrl')
            ->with('https://docs.typo3.org/other/typo3/view-helper-reference/13.4/en-us/viewhelpers.json')
            ->willReturn(['viewhelpers' => [
                'f:format.html' => ['summary' => 'Renders HTML.', 'path' => 'Global/Format/Html', 'anchor' => 'viewhelper-html'],
                // Nothing to link to
                'f:broken' => ['summary' => 'No page.'],
                'not an entry',
            ]]);

        $subject = $this->subject(['typo3_core_preferred' => '13.4']);

        self::assertSame(
            ['summary' => 'Renders HTML.', 'url' => 'https://docs.typo3.org/other/typo3/view-helper-reference/13.4/en-us/Global/Format/Html.html#viewhelper-html'],
            $subject->find('f:format.html'),
        );
        self::assertNull($subject->find('f:broken'));
        // Fetched once for the whole render
        self::assertNull($subject->find('f:unknown'));
    }

    public function testTheReferenceDoesNotLookUpItself(): void
    {
        $this->jsonLoader->expects(self::never())->method('loadJsonFromUrl');

        self::assertNull($this->subject(['interlink_shortcode' => 't3viewhelper'])->find('f:format.html'));
    }

    public function testAManualRendersWhenTheFileCannotBeFetched(): void
    {
        $this->jsonLoader->method('loadJsonFromUrl')->willThrowException(new RuntimeException('404'));

        self::assertNull($this->subject([])->find('f:format.html'));
    }

    /** @param array<string, string> $settings */
    private function subject(array $settings): ExternalViewHelpers
    {
        $anchorNormalizer = new SluggerAnchorNormalizer();
        $themeSettings = new Typo3DocsThemeSettings($settings);
        $versionService = new Typo3VersionService($themeSettings);

        return new ExternalViewHelpers(
            new Typo3InventoryRepository(
                new NullLogger(),
                $anchorNormalizer,
                new DefaultInventoryLoader(new NullLogger(), $this->jsonLoader, $anchorNormalizer),
                $this->jsonLoader,
                $versionService,
                [],
                new DefaultInterlinkParser($anchorNormalizer),
                new DefaultInventoryUrlBuilder($versionService),
            ),
            $this->jsonLoader,
            $themeSettings,
            new NullLogger(),
        );
    }
}
