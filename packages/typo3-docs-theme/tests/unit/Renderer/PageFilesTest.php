<?php

declare(strict_types=1);

namespace T3Docs\Typo3DocsTheme\Tests\Unit\Renderer;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use phpDocumentor\Guides\Settings\ProjectSettings;
use phpDocumentor\Guides\Settings\SettingsManager;
use T3Docs\Typo3DocsTheme\Renderer\PageFiles;

/**
 * The integration fixtures that compare toc.json all render HTML and Markdown,
 * so the formats a page is linked in when a project renders less are only
 * covered here.
 */
#[CoversClass(PageFiles::class)]
final class PageFilesTest extends TestCase
{
    /**
     * @param list<string> $outputFormats
     * @param array<string, string> $expected
     */
    #[DataProvider('outputFormats')]
    public function testNamesOnlyTheFilesThatWereRendered(array $outputFormats, array $expected): void
    {
        $projectSettings = new ProjectSettings();
        $projectSettings->setOutputFormats($outputFormats);

        $pageFiles = new PageFiles(new SettingsManager($projectSettings));

        self::assertSame($expected, $pageFiles->of('Chapter/Page'));
    }

    /** @return iterable<string, array{list<string>, array<string, string>}> */
    public static function outputFormats(): iterable
    {
        yield 'HTML and Markdown' => [
            ['html', 'interlink', 'md', 'tocjson'],
            ['html' => 'Chapter/Page.html', 'md' => 'Chapter/Page.md'],
        ];
        yield 'Markdown listed first, HTML still named first' => [
            ['md', 'html'],
            ['html' => 'Chapter/Page.html', 'md' => 'Chapter/Page.md'],
        ];
        yield 'HTML alone' => [
            ['html', 'tocjson'],
            ['html' => 'Chapter/Page.html'],
        ];
        yield 'Markdown alone' => [
            ['md', 'tocjson'],
            ['md' => 'Chapter/Page.md'],
        ];
        yield 'a single page has no file per page' => [
            ['singlepage', 'singlemd', 'tocjson'],
            [],
        ];
    }
}
