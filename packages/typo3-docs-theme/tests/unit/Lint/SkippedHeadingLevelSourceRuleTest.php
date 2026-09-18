<?php

declare(strict_types=1);

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use T3Docs\Typo3DocsTheme\Lint\SkippedHeadingLevelSourceRule;

final class SkippedHeadingLevelSourceRuleTest extends TestCase
{
    /**
     * @param list<string> $expectedHeadings headings (by text) expected to be flagged as skipping a level
     */
    #[Test]
    #[DataProvider('provider')]
    public function detectsSkippedHeadingLevels(string $contents, array $expectedHeadings): void
    {
        $warnings = (new SkippedHeadingLevelSourceRule())->lint($contents);

        self::assertCount(count($expectedHeadings), $warnings, implode(' | ', $warnings));
        foreach ($expectedHeadings as $heading) {
            self::assertNotEmpty(
                array_filter($warnings, static fn(string $w): bool => str_contains($w, '"' . $heading . '"')),
                $heading,
            );
        }
    }

    /**
     * @return iterable<string, array{string, list<string>}>
     */
    public static function provider(): iterable
    {
        yield 'no headings' => ["just a paragraph\nand another line\n", []];
        yield 'consistent nesting' => ["Aaaa\n====\n\nBbbb\n----\n", []];
        yield 'overline form' => ["====\nAaaa\n====\n\nBbbb\n----\n", []];

        // = (l1), - (l2), back to = (l1), then a brand-new ~ style => level 3 after level 1 = skip.
        yield 'skip via new deep style' => ["Aaaa\n====\n\nBbbb\n----\n\nCccc\n====\n\nDddd\n~~~~\n", ['Dddd']];

        // Adornment-like lines inside an indented literal/code block must be ignored.
        yield 'code block adornments ignored' => ["Title\n=====\n\nSub\n~~~~\n\n::\n\n   xx\n   ==\n   yy\n   ^^\n", []];

        // A genuine skip is still detected even when a code block sits in between.
        yield 'skip still detected past a code block' => ["Aaaa\n====\n\nBbbb\n----\n\nCccc\n====\n\n::\n\n    code\n    ~~~~\n\nDddd\n~~~~\n", ['Dddd']];

        // Underline shorter than the title is not a valid heading adornment.
        yield 'underline too short' => ["LongTitle\n==\n\nmore\n", []];
    }
}
