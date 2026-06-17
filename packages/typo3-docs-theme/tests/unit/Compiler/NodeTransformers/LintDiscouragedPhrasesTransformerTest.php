<?php

declare(strict_types=1);

use phpDocumentor\Guides\Compiler\CompilerContextInterface;
use phpDocumentor\Guides\Nodes\InlineCompoundNode;
use phpDocumentor\Guides\Nodes\Inline\PlainTextInlineNode;
use phpDocumentor\Guides\Nodes\SectionNode;
use phpDocumentor\Guides\Nodes\TitleNode;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Log\AbstractLogger;
use Stringable;
use T3Docs\Typo3DocsTheme\Compiler\NodeTransformers\LintDiscouragedPhrasesTransformer;
use T3Docs\Typo3DocsTheme\Settings\Typo3DocsThemeSettings;

final class LintDiscouragedPhrasesTransformerTest extends TestCase
{
    /**
     * @param array<string, string> $settings
     * @param list<string>          $expectedPhrases the discouraged phrases expected to be reported for $heading
     */
    #[Test]
    #[DataProvider('lintProvider')]
    public function reportsDiscouragedPhrasesInHeadings(array $settings, string $heading, array $expectedPhrases): void
    {
        $logger = new class () extends AbstractLogger {
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

        $transformer = new LintDiscouragedPhrasesTransformer(new Typo3DocsThemeSettings($settings), $logger);

        $section = new SectionNode(new TitleNode(new InlineCompoundNode([new PlainTextInlineNode($heading)]), 1, 'heading-id'));
        $transformer->enterNode($section, self::createMock(CompilerContextInterface::class));

        self::assertCount(count($expectedPhrases), $logger->warnings);
        foreach ($expectedPhrases as $phrase) {
            self::assertNotEmpty(
                array_filter($logger->warnings, static fn(string $w): bool => str_contains($w, '"' . $phrase . '"')),
                sprintf('Expected a warning for phrase "%s", got: %s', $phrase, implode(' | ', $logger->warnings)),
            );
        }
    }

    /**
     * @return iterable<string, array{array<string, string>, string, list<string>}>
     */
    public static function lintProvider(): iterable
    {
        // Opt-in: nothing happens unless `lint` is truthy.
        yield 'disabled by default' => [[], 'Installing in Non-Composer mode', []];
        yield 'disabled explicitly' => [['lint' => 'false'], 'Installing in Non-Composer mode', []];
        yield 'disabled on garbage token' => [['lint' => 'enabled'], 'Installing in Non-Composer mode', []];
        yield 'enabled via true' => [['lint' => 'true'], 'Installing in Non-Composer mode', ['Non-Composer mode']];
        yield 'enabled via 1' => [['lint' => '1'], 'Installing in Non-Composer mode', ['Non-Composer mode']];
        yield 'enabled via yes' => [['lint' => 'yes'], 'Installing in Non-Composer mode', ['Non-Composer mode']];
        yield 'enabled case-insensitive token' => [['lint' => 'TRUE'], 'Installing in Non-Composer mode', ['Non-Composer mode']];

        // Default phrase only triggers when present.
        yield 'no discouraged phrase' => [['lint' => 'true'], 'Installing with Composer', []];

        // Case-insensitive phrase matching.
        yield 'case-insensitive heading' => [['lint' => 'true'], 'The NON-COMPOSER MODE chapter', ['Non-Composer mode']];

        // Word-boundary matching: no false positive inside a larger word.
        yield 'word boundary no false positive' => [['lint' => 'true', 'lint_discouraged_phrases' => 'id'], 'The Identifier field', []];
        yield 'word boundary real match' => [['lint' => 'true', 'lint_discouraged_phrases' => 'id'], 'The id field', ['id']];

        // Custom phrase list replaces the default.
        yield 'custom list replaces default' => [['lint' => 'true', 'lint_discouraged_phrases' => 'foo, bar'], 'Non-Composer mode is fine here', []];
        yield 'custom list matches' => [['lint' => 'true', 'lint_discouraged_phrases' => 'foo, bar'], 'foo and bar', ['foo', 'bar']];

        // Whitespace and empty fragments are ignored; duplicates are de-duplicated (one warning, not two).
        yield 'whitespace and empty fragments' => [['lint' => 'true', 'lint_discouraged_phrases' => ' foo , , bar '], 'foo bar', ['foo', 'bar']];
        yield 'duplicate phrase warns once' => [['lint' => 'true', 'lint_discouraged_phrases' => 'foo, foo'], 'foo here', ['foo']];
    }
}
