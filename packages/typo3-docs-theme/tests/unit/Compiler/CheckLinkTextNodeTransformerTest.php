<?php

declare(strict_types=1);

namespace T3Docs\Typo3DocsTheme\Tests\Unit\Compiler;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use phpDocumentor\Guides\Compiler\CompilerContext;
use phpDocumentor\Guides\Nodes\DocumentNode;
use phpDocumentor\Guides\Nodes\Inline\DocReferenceNode;
use phpDocumentor\Guides\Nodes\Inline\PlainTextInlineNode;
use phpDocumentor\Guides\Nodes\Inline\ReferenceNode;
use phpDocumentor\Guides\Nodes\Node;
use phpDocumentor\Guides\Nodes\ProjectNode;
use Psr\Log\AbstractLogger;
use Stringable;
use T3Docs\Typo3DocsTheme\Compiler\NodeTransformers\CheckLinkTextNodeTransformer;
use T3Docs\Typo3DocsTheme\Directives\AbstractTypo3VersionChangeDirective;
use T3Docs\Typo3DocsTheme\Nodes\Metadata\CheckLinkTextNode;
use T3Docs\Typo3DocsTheme\Settings\Typo3DocsThemeSettings;

/**
 * The integration fixture's log is only checked for the lines it expects, so
 * that nothing else warns is shown here.
 */
#[CoversClass(CheckLinkTextNodeTransformer::class)]
final class CheckLinkTextNodeTransformerTest extends TestCase
{
    #[DataProvider('references')]
    public function testWarnsOnlyAboutAReferenceTheAuthorGaveNoText(Node $node, int $expectedWarnings): void
    {
        self::assertCount($expectedWarnings, $this->warningsFor($node, 'true'));
    }

    /** @return iterable<string, array{Node, int}> */
    public static function references(): iterable
    {
        $text = [new PlainTextInlineNode('Link text')];

        yield ':ref: without text' => [new ReferenceNode('some-label'), 1];
        yield ':doc: without text' => [new DocReferenceNode('Some/Page'), 1];
        yield ':ref: with text' => [new ReferenceNode('some-label', $text), 0];
        yield ':doc: with text' => [new DocReferenceNode('Some/Page', $text), 0];
        yield ':php:, which shows the class name' => [new ReferenceNode('\\Foo\\Bar', [], '', 'php:class'), 0];
        yield ':confval:, which shows the option name' => [new ReferenceNode('some-option', [], '', 'std:confval'), 0];

        $changelog = new ReferenceNode('feature-1-1', []);
        $changelog->setClasses([AbstractTypo3VersionChangeDirective::CHANGELOG_LINK_CLASS]);
        yield ':changelog: of versionchanged, which shows the entry title' => [$changelog, 0];
    }

    /** @return iterable<string, array{string, string|null, int}> */
    public static function switches(): iterable
    {
        yield 'manual default, no page field' => ['', null, 0];
        yield 'manual on, no page field' => ['true', null, 1];
        yield 'manual on, page off' => ['true', 'off', 0];
        yield 'manual on, page "false"' => ['true', 'false', 0];
        yield 'manual "false" is off' => ['false', null, 0];
        yield 'manual "no" is off' => ['no', null, 0];
        yield 'manual off, page on' => ['', 'on', 1];
    }

    #[DataProvider('switches')]
    public function testThePageFieldOverridesTheManual(string $manual, ?string $page, int $expectedWarnings): void
    {
        self::assertCount($expectedWarnings, $this->warningsFor(new ReferenceNode('some-label'), $manual, $page));
    }

    public function testTheWarningNamesTheInterlinkAndTheFix(): void
    {
        self::assertSame(
            ['The reference to "other-manual:some-label" has no link text, so it shows the title of its target. Give it its own: :ref:`Link text <other-manual:some-label>`.'],
            $this->warningsFor(new ReferenceNode('some-label', [], 'other-manual'), 'true'),
        );
    }

    /** @return list<string> */
    private function warningsFor(Node $node, string $manual, ?string $page = null): array
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
            $document->addHeaderNode(new CheckLinkTextNode($page));
        }
        $context = (new CompilerContext(new ProjectNode()))->withDocumentShadowTree($document);
        $transformer = new CheckLinkTextNodeTransformer(
            new Typo3DocsThemeSettings(['check_link_text' => $manual]),
            $logger,
        );

        self::assertTrue($transformer->supports($node));
        $transformer->leaveNode($node, $context);

        return $logger->warnings;
    }
}
