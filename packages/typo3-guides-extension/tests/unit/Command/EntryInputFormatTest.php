<?php

declare(strict_types=1);

namespace T3Docs\GuidesExtension\Tests\Unit\Command;

use phpDocumentor\Guides\Settings\ProjectSettings;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use T3Docs\GuidesExtension\Command\EntryInputFormat;

#[CoversClass(EntryInputFormat::class)]
final class EntryInputFormatTest extends TestCase
{
    /** @return iterable<string, array{?string, string, ?string}> */
    public static function formats(): iterable
    {
        yield 'one configured format' => ['rst', 'rst', 'rst'];
        yield 'the entry file decides over one configured format' => ['md', 'rst', 'md'];
        yield 'both formats configured, entry in reStructuredText' => ['rst', 'rst,md', 'rst,md'];
        yield 'both formats configured, entry in Markdown' => ['md', 'rst,md', 'rst,md'];
        yield 'configured list without the entry file\'s format' => ['md', 'rst,txt', 'md'];
        yield 'no entry file found' => [null, 'rst,md', null];
    }

    public function testAListIsNotPassedOnToGuidesThatCannotReadIt(): void
    {
        if (method_exists(ProjectSettings::class, 'getInputFormats')) {
            self::markTestSkipped('Only for phpdocumentor/guides without getInputFormats()');
        }

        $projectSettings = new ProjectSettings();
        $projectSettings->setInputFormat('rst,md');

        self::assertSame('rst', EntryInputFormat::keepConfiguredFormats('rst', $projectSettings));
    }

    #[DataProvider('formats')]
    public function testAConfiguredListWithTheEntryFormatIsKept(?string $entryFormat, string $configured, ?string $expected): void
    {
        if ($expected !== $entryFormat && !method_exists(ProjectSettings::class, 'getInputFormats')) {
            self::markTestSkipped('Needs phpdocumentor/guides with getInputFormats()');
        }

        $projectSettings = new ProjectSettings();
        $projectSettings->setInputFormat($configured);

        self::assertSame($expected, EntryInputFormat::keepConfiguredFormats($entryFormat, $projectSettings));
    }
}
