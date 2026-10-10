<?php

declare(strict_types=1);

namespace T3Docs\Typo3DocsTheme\Tests\Unit\TypoScriptReference;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use T3Docs\Typo3DocsTheme\TypoScriptReference\NamedConfval;

#[CoversClass(NamedConfval::class)]
final class NamedConfvalTest extends TestCase
{
    /** @param array{string, string} $expected */
    #[DataProvider('codes')]
    public function testSplitsTheCodeFromTheConfvalItNames(string $code, array $expected): void
    {
        self::assertSame($expected, NamedConfval::split($code));
    }

    /** @return iterable<string, array{string, array{string, string}}> */
    public static function codes(): iterable
    {
        yield 'own manual' => ['module.tx_extbase <some-key>', ['module.tx_extbase', 'some-key']];
        yield 'other manual' => ['current <t3tsref:stdwrap-current>', ['current', 't3tsref:stdwrap-current']];
        yield 'nothing named' => ['stdWrap.parseFunc', ['stdWrap.parseFunc', '']];
        yield 'an include is code' => ['<INCLUDE_TYPOSCRIPT: source="FILE:setup.typoscript">', ['<INCLUDE_TYPOSCRIPT: source="FILE:setup.typoscript">', '']];
        yield 'an operator is code' => ['lib.a =< lib.b', ['lib.a =< lib.b', '']];
        yield 'brackets alone are code' => ['<some-key>', ['<some-key>', '']];
        yield 'HTML in a wrap is code' => ['<div style="text-align:[*value*];"> | </div>', ['<div style="text-align:[*value*];"> | </div>', '']];
        yield 'a wrap ending in a tag is code' => ['value | <br>', ['value | <br>', '']];
        yield 'a key starting with an underscore' => ['_LOCAL_LANG <t3tsref:plugin-local-lang>', ['_LOCAL_LANG', 't3tsref:plugin-local-lang']];
        yield 'a path with a placeholder' => ['mod.wizards.newContentElement.wizardItems.[group] <t3tsref:some-key>', ['mod.wizards.newContentElement.wizardItems.[group]', 't3tsref:some-key']];
    }
}
