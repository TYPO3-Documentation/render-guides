<?php

declare(strict_types=1);

namespace T3Docs\Typo3DocsTheme\Tests\Unit\CodeFolding;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use phpDocumentor\Guides\Nodes\CodeNode;
use T3Docs\Typo3DocsTheme\CodeFolding\FoldedLines;

use function explode;

#[CoversClass(FoldedLines::class)]
final class FoldedLinesTest extends TestCase
{
    /** @param list<array{int, int}> $expected */
    #[DataProvider('phpHeaders')]
    public function testFoldsTheHeaderOfAPhpFile(string $code, array $expected): void
    {
        self::assertSame($expected, (new FoldedLines())->of(new CodeNode(explode("\n", $code), 'php')));
    }

    /** @return iterable<string, array{string, list<array{int, int}>}> */
    public static function phpHeaders(): iterable
    {
        yield 'open tag, declare, namespace and use, with the blank line after' => [
            "<?php\n\ndeclare(strict_types=1);\n\nnamespace Vendor\\Ext;\n\nuse Foo\\Bar;\n\nfinal class Baz {}\n",
            [[1, 8]],
        ];
        yield 'a license comment before the namespace goes with the header' => [
            "<?php\n/*\n * License\n */\nnamespace Vendor\\Ext;\n\nfinal class Baz {}\n",
            [[1, 6]],
        ];
        yield 'a docblock after the last use statement stays visible' => [
            "<?php\nnamespace Vendor\\Ext;\nuse Foo\\Bar;\n/**\n * Explains Baz.\n */\nfinal class Baz {}\n",
            [[1, 3]],
        ];
        yield 'a group use spanning lines' => [
            "<?php\nnamespace Vendor\\Ext;\nuse Foo\\{\n    Bar,\n    Qux,\n};\n\nreturn new Bar();\n",
            [[1, 7]],
        ];
        yield 'an attribute is code, not a comment' => [
            "<?php\nnamespace Vendor\\Ext;\nuse Foo\\Bar;\n#[Bar]\nfinal class Baz {}\n",
            [[1, 3]],
        ];
        yield 'too short to be worth a click' => [
            "<?php\n\nreturn ['key' => 'value'];\n",
            [],
        ];
        yield 'nothing but a header' => [
            "<?php\n\ndeclare(strict_types=1);\n\nnamespace Vendor\\Ext;\n",
            [],
        ];
        yield 'a braced namespace ends the header before it' => [
            "<?php\n\ndeclare(strict_types=1);\n\nnamespace Vendor\\Ext {\n    final class Baz {}\n}\n",
            [[1, 4]],
        ];
    }

    public function testFoldsNoHeaderOutsidePhp(): void
    {
        $code = "use: something\nnamespace: other\n\nkey: value\n";

        self::assertSame([], (new FoldedLines())->of(new CodeNode(explode("\n", $code), 'yaml')));
    }

    /** @param list<array{int, int}> $expected */
    #[DataProvider('visibleLines')]
    public function testFoldsWhatTheOptionLeavesOut(string $option, array $expected): void
    {
        $code = "<?php\n\ndeclare(strict_types=1);\n\nnamespace Vendor\\Ext;\n\nfinal class Baz\n{\n}\n";
        $node = (new CodeNode(explode("\n", $code), 'php'))->withOptions([FoldedLines::OPTION => $option]);
        self::assertInstanceOf(CodeNode::class, $node);

        self::assertSame($expected, (new FoldedLines())->of($node));
    }

    /** @return iterable<string, array{string, list<array{int, int}>}> */
    public static function visibleLines(): iterable
    {
        yield 'one range' => ['7-9', [[1, 6]]];
        yield 'two ranges' => ['3, 7-8', [[1, 2], [4, 6], [9, 9]]];
        yield 'the final newline is not a line' => ['1-8', [[9, 9]]];
        yield 'all switches folding off' => ['all', []];
        yield 'every line visible' => ['1-9', []];
        yield 'lines past the end are ignored' => ['7-40', [[1, 6]]];
        yield 'an invalid value falls back to the header' => ['from here', [[1, 6]]];
    }

    public function testNeverFoldsAnEmphasizedLine(): void
    {
        $code = "<?php\n\ndeclare(strict_types=1);\n\nnamespace Vendor\\Ext;\n\nuse Foo\\Bar;\n\nfinal class Baz {}\n";

        $header = new CodeNode(explode("\n", $code), 'php');
        $header->setEmphasizeLines('7');
        self::assertSame([[1, 6]], (new FoldedLines())->of($header), 'the header fold ends before it');

        $early = new CodeNode(explode("\n", $code), 'php');
        $early->setEmphasizeLines('2');
        self::assertSame([], (new FoldedLines())->of($early), 'what is left before it is too short to fold');

        $option = (new CodeNode(explode("\n", $code), 'php'))->withOptions([FoldedLines::OPTION => '9']);
        self::assertInstanceOf(CodeNode::class, $option);
        $option->setEmphasizeLines('3');
        self::assertSame([[1, 2], [4, 8]], (new FoldedLines())->of($option), 'the option shows it as well');
    }

    #[DataProvider('optionValues')]
    public function testValidatesTheOption(string $value, bool $valid): void
    {
        self::assertSame($valid, FoldedLines::isValid($value));
    }

    /** @return iterable<string, array{string, bool}> */
    public static function optionValues(): iterable
    {
        yield 'a line' => ['7', true];
        yield 'a range' => ['12-20', true];
        yield 'ranges with spaces' => [' 3 - 5 , 12-20 ', true];
        yield 'all' => ['ALL', true];
        yield 'words' => ['from here', false];
        yield 'a reversed range' => ['20-12', false];
        yield 'line zero' => ['0-3', false];
        yield 'empty' => ['', false];
    }
}
