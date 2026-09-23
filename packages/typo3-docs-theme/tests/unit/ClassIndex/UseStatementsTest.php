<?php

declare(strict_types=1);

namespace T3Docs\Typo3DocsTheme\Tests\Unit\ClassIndex;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use phpDocumentor\Guides\Nodes\CodeNode;
use T3Docs\Typo3DocsTheme\ClassIndex\UseStatements;

use function explode;

#[CoversClass(UseStatements::class)]
final class UseStatementsTest extends TestCase
{
    /** @param list<string> $expected */
    #[DataProvider('examples')]
    public function testFindsTheClassesAnExampleImports(string $code, array $expected): void
    {
        self::assertSame($expected, (new UseStatements())->of(new CodeNode(explode("\n", $code), 'php')));
    }

    /** @return iterable<string, array{string, list<string>}> */
    public static function examples(): iterable
    {
        yield 'one import' => [
            "<?php\nuse TYPO3\\CMS\\Core\\Utility\\GeneralUtility;\n",
            ['TYPO3\\CMS\\Core\\Utility\\GeneralUtility'],
        ];
        yield 'a leading backslash' => [
            "<?php\nuse \\TYPO3\\CMS\\Core\\Http\\ServerRequest;\n",
            ['\\TYPO3\\CMS\\Core\\Http\\ServerRequest'],
        ];
        yield 'renamed on import' => [
            "<?php\nuse Psr\\Log\\LoggerInterface as Logger;\n",
            ['Psr\\Log\\LoggerInterface'],
        ];
        yield 'a group of imports' => [
            "<?php\nuse TYPO3\\CMS\\Core\\Utility\\{GeneralUtility, PathUtility};\n",
            ['TYPO3\\CMS\\Core\\Utility\\GeneralUtility', 'TYPO3\\CMS\\Core\\Utility\\PathUtility'],
        ];
        yield 'a group with a renamed member' => [
            "<?php\nuse Vendor\\Ext\\{Foo as Bar, Deep\\Qux};\n",
            ['Vendor\\Ext\\Foo', 'Vendor\\Ext\\Deep\\Qux'],
        ];
        yield 'a function or a constant is no class' => [
            "<?php\nuse function TYPO3\\CMS\\Core\\Utility\\helper;\nuse const Vendor\\Ext\\LIMIT;\n",
            [],
        ];
        yield 'a trait of the example itself has no namespace' => [
            "<?php\nfinal class Example\n{\n    use SomeTrait;\n}\n",
            [],
        ];
        yield 'indented, and the body left alone' => [
            "<?php\n    use Vendor\\Ext\\Service;\n\n\$service = new Service();\nOther\\Thing::call();\n",
            ['Vendor\\Ext\\Service'],
        ];
        yield 'each import of several' => [
            "<?php\nuse A\\One;\nuse B\\Two;\nuse C\\Three;\n",
            ['A\\One', 'B\\Two', 'C\\Three'],
        ];
    }

    public function testReadsNothingButPhp(): void
    {
        $code = "use Vendor\\Ext\\Service;";

        self::assertSame([], (new UseStatements())->of(new CodeNode([$code], 'typoscript')));
        self::assertSame([], (new UseStatements())->of(new CodeNode([$code], null)));
    }
}
