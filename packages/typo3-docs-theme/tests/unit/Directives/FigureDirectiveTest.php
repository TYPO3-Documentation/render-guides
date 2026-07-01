<?php

declare(strict_types=1);

namespace T3Docs\Typo3DocsTheme\Tests\Unit\Directives;

use phpDocumentor\Guides\Nodes\CollectionNode;
use phpDocumentor\Guides\Nodes\FigureNode;
use phpDocumentor\Guides\Nodes\InlineCompoundNode;
use phpDocumentor\Guides\ReferenceResolvers\DocumentNameResolverInterface;
use phpDocumentor\Guides\RestructuredText\Parser\BlockContext;
use phpDocumentor\Guides\RestructuredText\Parser\Directive;
use phpDocumentor\Guides\RestructuredText\Parser\DirectiveOption;
use phpDocumentor\Guides\RestructuredText\Parser\DocumentParserContext;
use phpDocumentor\Guides\RestructuredText\Parser\Productions\Rule;
use phpDocumentor\Guides\ParserContext;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use T3Docs\Typo3DocsTheme\Directives\FigureDirective;

final class FigureDirectiveTest extends TestCase
{
    private FigureDirective $subject;
    private DocumentNameResolverInterface&MockObject $documentNameResolver;
    /** @var Rule<CollectionNode>&MockObject */
    private Rule&MockObject $startingRule;
    private LoggerInterface&MockObject $logger;

    protected function setUp(): void
    {
        $this->documentNameResolver = $this->createMock(DocumentNameResolverInterface::class);
        $this->documentNameResolver->method('absoluteUrl')->willReturn('/resolved/image.png');
        $this->startingRule = $this->createMock(Rule::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->subject = new FigureDirective(
            $this->documentNameResolver,
            $this->startingRule,
            $this->logger,
        );
    }

    #[Test]
    public function getNameReturnsFigure(): void
    {
        self::assertSame('figure', $this->subject->getName());
    }

    #[Test]
    public function processReturnsNullWhenStartingRuleReturnsNull(): void
    {
        $this->startingRule->method('apply')->willReturn(null);

        $result = $this->subject->process(
            $this->createBlockContext(),
            new Directive('', 'figure', 'image.png'),
        );

        self::assertNull($result);
    }

    #[Test]
    public function processRewritesLegacyFloatLeftClass(): void
    {
        $this->startingRule->method('apply')->willReturn(new CollectionNode([new InlineCompoundNode([])]));

        $directive = new Directive('', 'figure', 'image.png', [
            'class' => new DirectiveOption('class', 'float-left'),
        ]);

        $this->logger->expects(self::once())
            ->method('warning')
            ->with(self::stringContains('deprecated'));

        $result = $this->subject->process($this->createBlockContext(), $directive);

        self::assertInstanceOf(FigureNode::class, $result);
        // The directive class option should be rewritten for postProcessNode
        self::assertSame('float-start', $directive->getOption('class')->getValue());
        // The inner image should NOT have float classes
        self::assertNull($result->getImage()->getOption('class'));
    }

    #[Test]
    public function processRewritesLegacyFloatRightClass(): void
    {
        $this->startingRule->method('apply')->willReturn(new CollectionNode([new InlineCompoundNode([])]));

        $directive = new Directive('', 'figure', 'image.png', [
            'class' => new DirectiveOption('class', 'float-right'),
        ]);

        $this->logger->expects(self::once())->method('warning');

        $result = $this->subject->process($this->createBlockContext(), $directive);

        self::assertInstanceOf(FigureNode::class, $result);
        self::assertSame('float-end', $directive->getOption('class')->getValue());
        self::assertNull($result->getImage()->getOption('class'));
    }

    #[Test]
    public function processPreservesNonFloatClassesOnInnerImage(): void
    {
        $this->startingRule->method('apply')->willReturn(new CollectionNode([new InlineCompoundNode([])]));

        $directive = new Directive('', 'figure', 'image.png', [
            'class' => new DirectiveOption('class', 'with-shadow float-left'),
        ]);

        $this->logger->expects(self::once())->method('warning');

        $result = $this->subject->process($this->createBlockContext(), $directive);

        self::assertInstanceOf(FigureNode::class, $result);
        // Directive updated with rewritten classes
        self::assertSame('with-shadow float-start', $directive->getOption('class')->getValue());
        // Inner image gets only non-float classes
        self::assertSame('with-shadow', $result->getImage()->getOption('class'));
    }

    #[Test]
    public function processDoesNotRewriteModernClasses(): void
    {
        $this->startingRule->method('apply')->willReturn(new CollectionNode([new InlineCompoundNode([])]));

        $directive = new Directive('', 'figure', 'image.png', [
            'class' => new DirectiveOption('class', 'float-start'),
        ]);

        $this->logger->expects(self::never())->method('warning');

        $result = $this->subject->process($this->createBlockContext(), $directive);

        self::assertInstanceOf(FigureNode::class, $result);
        // Float classes stripped from inner image
        self::assertNull($result->getImage()->getOption('class'));
    }

    #[Test]
    public function processHandlesNoClassOption(): void
    {
        $this->startingRule->method('apply')->willReturn(new CollectionNode([new InlineCompoundNode([])]));

        $directive = new Directive('', 'figure', 'image.png');

        $this->logger->expects(self::never())->method('warning');

        $result = $this->subject->process($this->createBlockContext(), $directive);

        self::assertInstanceOf(FigureNode::class, $result);
        self::assertNull($result->getImage()->getOption('class'));
    }

    #[Test]
    public function processHandlesNonStringClassValue(): void
    {
        $this->startingRule->method('apply')->willReturn(new CollectionNode([new InlineCompoundNode([])]));

        $directive = new Directive('', 'figure', 'image.png', [
            'class' => new DirectiveOption('class', true),
        ]);

        $this->logger->expects(self::never())->method('warning');

        $result = $this->subject->process($this->createBlockContext(), $directive);

        self::assertInstanceOf(FigureNode::class, $result);
    }

    #[Test]
    public function processFiltersInvalidZoomMode(): void
    {
        $this->startingRule->method('apply')->willReturn(new CollectionNode([new InlineCompoundNode([])]));

        $directive = new Directive('', 'figure', 'image.png', [
            'zoom' => new DirectiveOption('zoom', 'invalid-mode'),
        ]);

        $result = $this->subject->process($this->createBlockContext(), $directive);

        self::assertInstanceOf(FigureNode::class, $result);
        self::assertNull($result->getOption('zoom'));
    }

    #[Test]
    #[DataProvider('validZoomModeProvider')]
    public function processAcceptsValidZoomModes(string $mode): void
    {
        $this->startingRule->method('apply')->willReturn(new CollectionNode([new InlineCompoundNode([])]));

        $directive = new Directive('', 'figure', 'image.png', [
            'zoom' => new DirectiveOption('zoom', $mode),
        ]);

        $result = $this->subject->process($this->createBlockContext(), $directive);

        self::assertInstanceOf(FigureNode::class, $result);
        self::assertSame($mode, $result->getOption('zoom'));
    }

    /** @return \Generator<string, array{string}> */
    public static function validZoomModeProvider(): \Generator
    {
        yield 'lightbox' => ['lightbox'];
        yield 'gallery' => ['gallery'];
        yield 'inline' => ['inline'];
        yield 'lens' => ['lens'];
    }

    #[Test]
    public function processPassesImageOptionsToImageNode(): void
    {
        $this->startingRule->method('apply')->willReturn(new CollectionNode([new InlineCompoundNode([])]));

        $directive = new Directive('', 'figure', 'image.png', [
            'width' => new DirectiveOption('width', '200'),
            'height' => new DirectiveOption('height', '100'),
            'alt' => new DirectiveOption('alt', 'test image'),
            'scale' => new DirectiveOption('scale', '50'),
        ]);

        $result = $this->subject->process($this->createBlockContext(), $directive);

        self::assertInstanceOf(FigureNode::class, $result);
        self::assertSame('200', $result->getImage()->getOption('width'));
        self::assertSame('100', $result->getImage()->getOption('height'));
        self::assertSame('test image', $result->getImage()->getOption('alt'));
        self::assertSame('50', $result->getImage()->getOption('scale'));
    }

    #[Test]
    public function processStripsFloatStartFromInnerImage(): void
    {
        $this->startingRule->method('apply')->willReturn(new CollectionNode([new InlineCompoundNode([])]));

        $directive = new Directive('', 'figure', 'image.png', [
            'class' => new DirectiveOption('class', 'float-end with-border'),
        ]);

        $result = $this->subject->process($this->createBlockContext(), $directive);

        self::assertInstanceOf(FigureNode::class, $result);
        // Float-end stripped, only with-border remains
        self::assertSame('with-border', $result->getImage()->getOption('class'));
    }

    private function createBlockContext(): BlockContext
    {
        $parserContext = $this->createMock(ParserContext::class);
        $parserContext->method('getCurrentAbsolutePath')->willReturn('/test');
        $parserContext->method('getLoggerInformation')->willReturn(['rst-file' => 'test.rst']);
        $parserContext->method('getInitialHeaderLevel')->willReturn(1);

        $documentParserContext = $this->createMock(DocumentParserContext::class);
        $documentParserContext->method('getContext')->willReturn($parserContext);
        $documentParserContext->method('getLoggerInformation')->willReturn(['rst-file' => 'test.rst']);

        return new BlockContext($documentParserContext, '', false, 0);
    }
}
