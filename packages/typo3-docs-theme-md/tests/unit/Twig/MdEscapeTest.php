<?php

declare(strict_types=1);

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use T3Docs\Typo3DocsThemeMd\Twig\MdExtension;

/**
 * Source prose is not Markdown. What gets escaped is a balance: too little and
 * the document changes meaning, too much and the output fills with backslashes
 * a reader has to look past.
 */
final class MdEscapeTest extends TestCase
{
    private MdExtension $subject;

    protected function setUp(): void
    {
        // The node renderer is only used by the table helper, not by escaping.
        $this->subject = (new ReflectionClass(MdExtension::class))->newInstanceWithoutConstructor();
    }

    /** @return array<string, array{string, string}> */
    public static function textIsEscaped(): array
    {
        return [
            // Emphasis: an underscore inside a word cannot open emphasis in
            // CommonMark, one at a word boundary can.
            'underscore inside a word is left alone' => ['snake_case_name', 'snake_case_name'],
            'underscore at a word boundary is escaped' => ['__dunder__', '\_\_dunder\_\_'],
            'underscore between words is escaped' => ['a _ b', 'a \_ b'],
            'asterisk is escaped even inside a word' => ['2*3*4', '2\*3\*4'],

            // Links and code spans.
            'brackets are escaped' => ['see [1] and array[0](x)', 'see \[1\] and array\[0\](x)'],
            'backtick is escaped' => ['use ` to quote', 'use \` to quote'],

            // An angle bracket only starts something before a letter.
            'angle bracket before a letter is escaped' => ['<b>bold</b>', '\<b>bold\</b>'],
            'angle bracket as a comparison is left alone' => ['3 < 4', '3 < 4'],

            // Block markers count at the start of a line, and only with a space
            // after them.
            'hash at line start is escaped' => ["# not a heading", '\# not a heading'],
            'hash without a space is left alone' => ['#tag', '#tag'],
            'dash at line start is escaped' => ['- not a list item', '\- not a list item'],
            'dash without a space is left alone' => ['-1 degree', '-1 degree'],
            'quote marker at line start is escaped' => ['> not a quote', '\> not a quote'],
            'ordered marker is escaped at the dot, not the digits' => ['1. not ordered', '1\. not ordered'],
            'number without a space is left alone' => ['1.5 metres', '1.5 metres'],
            'marker mid-sentence is left alone' => ['a - b # c > d', 'a - b # c > d'],

            // A literal backslash has to survive as one.
            'backslash is doubled' => ['C:\temp\file', 'C:\\\\temp\\\\file'],

            // Nothing to do.
            'plain text is untouched' => ['Just a sentence.', 'Just a sentence.'],
            'empty string stays empty' => ['', ''],
        ];
    }

    #[Test]
    #[DataProvider('textIsEscaped')]
    public function textIsEscapedForMarkdown(string $input, string $expected): void
    {
        self::assertSame($expected, $this->subject->escape($input));
    }

    /** @return array<string, array{string, string, string}> */
    public static function emphasisKeepsSpacesOutside(): array
    {
        return [
            // reStructuredText allows emphasis whose content starts or ends
            // with a space; Markdown reads "* 3 *" as literal text.
            'leading and trailing space move out' => [' 3 ', '*', ' *3* '],
            'leading space moves out' => [' text', '*', ' *text*'],
            'no spaces to move' => ['text', '**', '**text**'],
            'only whitespace is left as it is' => ['   ', '*', '   '],
            'empty string is left as it is' => ['', '**', ''],
        ];
    }

    #[Test]
    #[DataProvider('emphasisKeepsSpacesOutside')]
    public function emphasisKeepsSurroundingSpacesOutsideTheMarker(
        string $input,
        string $marker,
        string $expected,
    ): void {
        self::assertSame($expected, $this->subject->wrap($input, $marker));
    }
}
