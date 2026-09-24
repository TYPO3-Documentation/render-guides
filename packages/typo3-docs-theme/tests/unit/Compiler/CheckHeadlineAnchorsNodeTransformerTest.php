<?php

declare(strict_types=1);

namespace T3Docs\Typo3DocsTheme\Tests\Unit\Compiler;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use phpDocumentor\Guides\Compiler\CompilerContext;
use phpDocumentor\Guides\Nodes\AnchorNode;
use phpDocumentor\Guides\Nodes\DocumentNode;
use phpDocumentor\Guides\Nodes\InlineCompoundNode;
use phpDocumentor\Guides\Nodes\Inline\PlainTextInlineNode;
use phpDocumentor\Guides\Nodes\ProjectNode;
use phpDocumentor\Guides\Nodes\SectionNode;
use phpDocumentor\Guides\Nodes\TitleNode;
use phpDocumentor\Guides\Settings\ProjectSettings;
use phpDocumentor\Guides\Settings\SettingsManager;
use Psr\Log\AbstractLogger;
use Stringable;
use T3Docs\Typo3DocsTheme\Compiler\NodeTransformers\CheckHeadlineAnchorsNodeTransformer;
use T3Docs\Typo3DocsTheme\Nodes\Metadata\CheckHeadlineAnchorsNode;
use T3Docs\Typo3DocsTheme\Settings\Typo3DocsThemeSettings;

/**
 * The integration fixture's log is only checked for the lines it expects, so
 * that nothing else warns is shown here.
 */
#[CoversClass(CheckHeadlineAnchorsNodeTransformer::class)]
final class CheckHeadlineAnchorsNodeTransformerTest extends TestCase
{
    public function testWarnsAboutAHeadlineWithoutAnchorAndSuggestsOne(): void
    {
        self::assertSame(
            ['The headline "Inline columns" has no anchor, so no permalink leads to it. Give it one: ..  _inline-columns:'],
            $this->warningsFor($this->section(anchored: false), 'true'),
        );
    }

    public function testAHeadlineWithAnAnchorDoesNotWarn(): void
    {
        self::assertSame([], $this->warningsFor($this->section(anchored: true), 'true'));
    }

    /** @return iterable<string, array{string, string|null, string, int}> */
    public static function switches(): iterable
    {
        yield 'manual default, no page field' => ['', null, 'rst', 0];
        yield 'manual on, no page field' => ['true', null, 'rst', 1];
        yield 'manual on, page off' => ['true', 'off', 'rst', 0];
        yield 'manual "false" is off' => ['false', null, 'rst', 0];
        yield 'manual off, page on' => ['', 'on', 'rst', 1];
        yield 'a Markdown project, although switched on' => ['true', null, 'md', 0];
        yield 'a Markdown project, although its page switches it on' => ['', 'on', 'md', 0];
    }

    #[DataProvider('switches')]
    public function testThePageFieldOverridesTheManualExceptInMarkdown(string $manual, ?string $page, string $inputFormat, int $expectedWarnings): void
    {
        self::assertCount($expectedWarnings, $this->warningsFor($this->section(anchored: false), $manual, $page, $inputFormat));
    }

    private function section(bool $anchored): SectionNode
    {
        $section = new SectionNode(new TitleNode(new InlineCompoundNode([new PlainTextInlineNode('Inline columns')]), 2, 'inline-columns'));
        if ($anchored) {
            $section->addChildNode(new AnchorNode('inline-columns'));
        }

        return $section;
    }

    /** @return list<string> */
    private function warningsFor(SectionNode $section, string $manual, ?string $page = null, string $inputFormat = 'rst'): array
    {
        $logger = new class () extends AbstractLogger {
            /** @var list<string> */
            public array $warnings = [];

            public function log($level, string|Stringable $message, array $context = []): void
            {
                $this->warnings[] = (string) $message;
            }
        };
        $document = new DocumentNode('hash', 'index');
        if ($page !== null) {
            $document->addHeaderNode(new CheckHeadlineAnchorsNode($page));
        }
        $projectSettings = new ProjectSettings();
        $projectSettings->setInputFormat($inputFormat);
        $context = (new CompilerContext(new ProjectNode()))->withDocumentShadowTree($document);
        $transformer = new CheckHeadlineAnchorsNodeTransformer(
            new Typo3DocsThemeSettings(['check_headline_anchors' => $manual]),
            new SettingsManager($projectSettings),
            $logger,
        );

        self::assertTrue($transformer->supports($section));
        $transformer->leaveNode($section, $context);

        return $logger->warnings;
    }
}
