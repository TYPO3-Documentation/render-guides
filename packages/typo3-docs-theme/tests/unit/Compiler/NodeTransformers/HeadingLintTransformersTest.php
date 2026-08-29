<?php

declare(strict_types=1);

use phpDocumentor\Guides\Compiler\CompilerContextInterface;
use phpDocumentor\Guides\Nodes\AnchorNode;
use phpDocumentor\Guides\Nodes\InlineCompoundNode;
use phpDocumentor\Guides\Nodes\Inline\PlainTextInlineNode;
use phpDocumentor\Guides\Nodes\SectionNode;
use phpDocumentor\Guides\Nodes\TitleNode;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Log\AbstractLogger;
use T3Docs\Typo3DocsTheme\Compiler\NodeTransformers\MissingAnchorHeadingLintTransformer;
use T3Docs\Typo3DocsTheme\Compiler\NodeTransformers\SentenceCaseHeadingLintTransformer;
use T3Docs\Typo3DocsTheme\Settings\Typo3DocsThemeSettings;

final class HeadingLintTransformersTest extends TestCase
{
    /**
     * @param array<string, string> $settings
     */
    #[Test]
    #[DataProvider('sentenceCaseProvider')]
    public function sentenceCaseRuleFlagsTitleCaseHeadings(array $settings, string $heading, bool $expectWarning): void
    {
        $logger = self::spyLogger();
        $transformer = new SentenceCaseHeadingLintTransformer(new Typo3DocsThemeSettings($settings), $logger);
        $transformer->enterNode(self::section($heading), self::createMock(CompilerContextInterface::class));

        self::assertCount($expectWarning ? 1 : 0, $logger->warnings);
    }

    /**
     * @return iterable<string, array{array<string, string>, string, bool}>
     */
    public static function sentenceCaseProvider(): iterable
    {
        yield 'sentence case is fine' => [['lint' => 'true'], 'Installing the extension manager', false];
        yield 'title case is flagged' => [['lint' => 'true'], 'Installing the Extension Manager', true];
        yield 'single capitalized word is allowed' => [['lint' => 'true'], 'Working with Fluid', false];
        yield 'acronyms are not flagged' => [['lint' => 'true'], 'Configuring TYPO3 and API access', false];
        yield 'camel case identifiers are not flagged' => [['lint' => 'true'], 'Using the ViewHelper base class', false];
        yield 'default proper nouns are allowed' => [['lint' => 'true'], 'Using Composer and Docker together', false];
        yield 'custom allow list is honoured' => [['lint' => 'true', 'lint_heading_allowed_words' => 'Foo, Bar'], 'The Foo and Bar widgets', false];
        yield 'single word heading' => [['lint' => 'true'], 'Installation', false];
        yield 'disabled by default' => [[], 'Installing the Extension Manager', false];
    }

    /**
     * @param array<string, string> $settings
     */
    #[Test]
    #[DataProvider('missingAnchorProvider')]
    public function missingAnchorRuleFlagsUnanchoredSubHeadings(array $settings, int $level, bool $withAnchor, bool $expectWarning): void
    {
        $logger = self::spyLogger();
        $transformer = new MissingAnchorHeadingLintTransformer(new Typo3DocsThemeSettings($settings), $logger);

        $section = self::section('Some Heading', $level);
        if ($withAnchor) {
            $section->addChildNode(new AnchorNode('some-label'));
        }
        $transformer->enterNode($section, self::createMock(CompilerContextInterface::class));

        self::assertCount($expectWarning ? 1 : 0, $logger->warnings);
    }

    /**
     * @return iterable<string, array{array<string, string>, int, bool, bool}>
     */
    public static function missingAnchorProvider(): iterable
    {
        yield 'sub-heading without anchor is flagged' => [['lint' => 'true'], 2, false, true];
        yield 'sub-heading with anchor is fine' => [['lint' => 'true'], 2, true, false];
        yield 'document title is exempt' => [['lint' => 'true'], 1, false, false];
        yield 'disabled by default' => [[], 2, false, false];
    }

    private static function section(string $heading, int $level = 2): SectionNode
    {
        return new SectionNode(new TitleNode(new InlineCompoundNode([new PlainTextInlineNode($heading)]), $level, 'heading-id'));
    }

    private static function spyLogger(): AbstractLogger
    {
        return new class () extends AbstractLogger {
            /** @var list<string> */
            public array $warnings = [];

            /** @param mixed[] $context */
            public function log($level, string|Stringable $message, array $context = []): void
            {
                if ($level === 'warning') {
                    $this->warnings[] = (string) $message;
                }
            }
        };
    }
}
