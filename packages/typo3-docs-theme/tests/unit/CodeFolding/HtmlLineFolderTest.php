<?php

declare(strict_types=1);

namespace T3Docs\Typo3DocsTheme\Tests\Unit\CodeFolding;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use T3Docs\Typo3DocsTheme\CodeFolding\HtmlLineFolder;

use function html_entity_decode;
use function sprintf;
use function strip_tags;

#[CoversClass(HtmlLineFolder::class)]
final class HtmlLineFolderTest extends TestCase
{
    private const TOGGLE = '<button type="button" class="code-fold-toggle" data-fold-label="%s" aria-label="Show %s"></button>';

    public function testLeavesTheMarkupAloneWithoutRanges(): void
    {
        $html = '<pre><code>a' . "\n" . 'b</code></pre>';

        self::assertSame($html, (new HtmlLineFolder())->fold($html, []));
    }

    public function testWrapsARangeWithTheNewlineThatEndsIt(): void
    {
        $html = "<pre class=\"code-block\"><code class=\"hljs php\">one\ntwo\nthree\nfour</code></pre>";

        self::assertSame(
            '<pre class="code-block"><code class="hljs php">one' . "\n"
            . self::toggle('2 lines') . '<span class="code-fold">two' . "\n" . 'three' . "\n" . '</span>'
            . 'four</code></pre>',
            (new HtmlLineFolder())->fold($html, [[2, 3]]),
        );
    }

    public function testSaysLineForOne(): void
    {
        $folded = (new HtmlLineFolder())->fold("<pre><code>one\ntwo</code></pre>", [[2, 2]]);

        self::assertStringContainsString(self::toggle('1 line'), $folded);
    }

    public function testClosesAndReopensASpanCrossingTheFold(): void
    {
        $html = "<pre><code><span class=\"hljs-comment\">/* one\ntwo */</span>\nthree</code></pre>";

        self::assertSame(
            '<pre><code>' . self::toggle('1 line')
            . '<span class="code-fold"><span class="hljs-comment">/* one</span>' . "\n" . '</span>'
            . '<span class="hljs-comment">two */</span>' . "\n"
            . 'three</code></pre>',
            (new HtmlLineFolder())->fold($html, [[1, 1]]),
        );
    }

    /**
     * Line numbers are wrapped around each line after highlighting, without
     * regard to the highlighter's spans. Each line must keep exactly its own.
     */
    public function testKeepsEachLineItsOwnNumber(): void
    {
        $html = '<pre><code>'
            . '<span data-line-number="1"><span class="hljs-comment">/* one</span>' . "\n"
            . '<span data-line-number="2">two */</span></span>' . "\n"
            . '<span data-emphasize-line><span data-line-number="3">three</span></span>'
            . '</code></pre>';

        self::assertSame(
            '<pre><code>' . self::toggle('1 line')
            . '<span class="code-fold"><span data-line-number="1"><span class="hljs-comment">/* one</span></span>' . "\n" . '</span>'
            . '<span data-line-number="2"><span class="hljs-comment">two */</span></span>' . "\n"
            . '<span data-emphasize-line><span data-line-number="3">three</span></span>'
            . '</code></pre>',
            (new HtmlLineFolder())->fold($html, [[1, 1]]),
        );
    }

    public function testKeepsEveryCharacterOfTheCode(): void
    {
        $html = "<pre><code><span class=\"a\">&lt;?php\n\nuse <span class=\"b\">Foo</span>;\n</span>\nreturn 1;\n</code></pre>";

        $folded = (new HtmlLineFolder())->fold($html, [[1, 3], [5, 5]]);

        self::assertSame(
            html_entity_decode(strip_tags($html)),
            html_entity_decode(strip_tags($folded)),
        );
    }

    private static function toggle(string $label): string
    {
        return sprintf(self::TOGGLE, $label, $label);
    }
}
