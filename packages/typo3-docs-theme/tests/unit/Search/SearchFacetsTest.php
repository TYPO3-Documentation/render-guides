<?php

declare(strict_types=1);

namespace T3Docs\Typo3DocsTheme\Tests\Unit\Search;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use T3Docs\Typo3DocsTheme\Search\SearchFacets;

#[CoversClass(SearchFacets::class)]
final class SearchFacetsTest extends TestCase
{
    #[DataProvider('values')]
    public function testFilesAValueUnderItsFacetOrAsOption(string $value, string $expected): void
    {
        self::assertSame($expected, (new SearchFacets())->of($value));
    }

    /** @return iterable<string, array{string, string}> */
    public static function values(): iterable
    {
        yield 'a facet' => ['TCA', 'TCA'];
        yield 'a facet of TYPO3 Explained' => ['PHP Configuration File', 'PHP Configuration File'];
        yield 'surrounding spaces' => [' JavaScript Option ', 'JavaScript Option'];
        yield 'another case' => ['javascript option', 'Option'];
        yield 'not a facet' => ['Expression Language', 'Option'];
        yield 'empty' => ['', 'Option'];
    }
}
