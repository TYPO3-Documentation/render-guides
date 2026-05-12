<?php

declare(strict_types=1);

namespace T3Docs\Typo3DocsTheme\Tests\Unit\Directives;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use T3Docs\Typo3DocsTheme\Directives\RewritesLegacyFloatClasses;

final class RewritesLegacyFloatClassesTest extends TestCase
{
    private object $subject;

    protected function setUp(): void
    {
        // Anonymous class to expose the private trait methods for testing
        $this->subject = new class () {
            use RewritesLegacyFloatClasses;

            public function callHasLegacyFloatClass(string $classValue): bool
            {
                return $this->hasLegacyFloatClass($classValue);
            }

            public function callRewriteLegacyFloatClasses(string $classValue): string
            {
                return $this->rewriteLegacyFloatClasses($classValue);
            }
        };
    }

    #[Test]
    #[DataProvider('legacyFloatClassDetectionProvider')]
    public function hasLegacyFloatClassDetectsCorrectly(string $classValue, bool $expected): void
    {
        self::assertSame($expected, $this->subject->callHasLegacyFloatClass($classValue));
    }

    /** @return \Generator<string, array{string, bool}> */
    public static function legacyFloatClassDetectionProvider(): \Generator
    {
        yield 'float-left' => ['float-left', true];
        yield 'float-right' => ['float-right', true];
        yield 'float-left with other classes' => ['with-shadow float-left', true];
        yield 'float-right with other classes' => ['float-right with-border', true];
        yield 'both legacy classes' => ['float-left float-right', true];
        yield 'modern float-start' => ['float-start', false];
        yield 'modern float-end' => ['float-end', false];
        yield 'no float classes' => ['with-shadow with-border', false];
        yield 'empty string' => ['', false];
        yield 'partial match float-leftover' => ['float-leftover', false];
        yield 'partial match afloat-right' => ['afloat-right', false];
    }

    #[Test]
    #[DataProvider('legacyFloatClassRewriteProvider')]
    public function rewriteLegacyFloatClassesRewritesCorrectly(string $classValue, string $expected): void
    {
        self::assertSame($expected, $this->subject->callRewriteLegacyFloatClasses($classValue));
    }

    /** @return \Generator<string, array{string, string}> */
    public static function legacyFloatClassRewriteProvider(): \Generator
    {
        yield 'float-left to float-start' => ['float-left', 'float-start'];
        yield 'float-right to float-end' => ['float-right', 'float-end'];
        yield 'preserves other classes' => ['with-shadow float-left', 'with-shadow float-start'];
        yield 'rewrites both' => ['float-left float-right', 'float-start float-end'];
        yield 'modern classes unchanged' => ['float-start', 'float-start'];
        yield 'no float classes unchanged' => ['with-shadow', 'with-shadow'];
        yield 'empty string unchanged' => ['', ''];
    }
}
