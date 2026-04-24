<?php

declare(strict_types=1);

namespace T3Docs\Typo3DocsTheme\Tests\Unit\Directives;

use phpDocumentor\Guides\Nodes\ImageNode;
use phpDocumentor\Guides\ReferenceResolvers\DocumentNameResolverInterface;
use phpDocumentor\Guides\RestructuredText\Parser\BlockContext;
use phpDocumentor\Guides\RestructuredText\Parser\Directive;
use phpDocumentor\Guides\RestructuredText\Parser\DirectiveOption;
use phpDocumentor\Guides\RestructuredText\Parser\DocumentParserContext;
use phpDocumentor\Guides\ParserContext;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use T3Docs\Typo3DocsTheme\Directives\ImageDirective;

final class ImageDirectiveTest extends TestCase
{
    private ImageDirective $subject;
    private DocumentNameResolverInterface&MockObject $documentNameResolver;
    private LoggerInterface&MockObject $logger;

    protected function setUp(): void
    {
        $this->documentNameResolver = $this->createMock(DocumentNameResolverInterface::class);
        $this->documentNameResolver->method('absoluteUrl')->willReturn('/resolved/image.png');
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->subject = new ImageDirective($this->documentNameResolver, $this->logger);
    }

    #[Test]
    public function getNameReturnsImage(): void
    {
        self::assertSame('image', $this->subject->getName());
    }

    #[Test]
    public function processRewritesLegacyFloatLeftClass(): void
    {
        $directive = new Directive('', 'image', 'image.png', [
            'class' => new DirectiveOption('class', 'float-left'),
        ]);

        $this->logger->expects(self::once())
            ->method('warning')
            ->with(self::stringContains('deprecated'));

        $result = $this->subject->process($this->createBlockContext(), $directive);

        self::assertInstanceOf(ImageNode::class, $result);
        self::assertSame('float-start', $directive->getOption('class')->getValue());
    }

    #[Test]
    public function processRewritesLegacyFloatRightClass(): void
    {
        $directive = new Directive('', 'image', 'image.png', [
            'class' => new DirectiveOption('class', 'float-right'),
        ]);

        $this->logger->expects(self::once())
            ->method('warning')
            ->with(self::stringContains('deprecated'));

        $result = $this->subject->process($this->createBlockContext(), $directive);

        self::assertInstanceOf(ImageNode::class, $result);
        self::assertSame('float-end', $directive->getOption('class')->getValue());
    }

    #[Test]
    public function processRewritesLegacyClassWithOtherClasses(): void
    {
        $directive = new Directive('', 'image', 'image.png', [
            'class' => new DirectiveOption('class', 'with-shadow float-left'),
        ]);

        $this->logger->expects(self::once())->method('warning');

        $result = $this->subject->process($this->createBlockContext(), $directive);

        self::assertInstanceOf(ImageNode::class, $result);
        self::assertSame('with-shadow float-start', $directive->getOption('class')->getValue());
    }

    #[Test]
    public function processDoesNotRewriteModernClasses(): void
    {
        $directive = new Directive('', 'image', 'image.png', [
            'class' => new DirectiveOption('class', 'float-start'),
        ]);

        $this->logger->expects(self::never())->method('warning');

        $result = $this->subject->process($this->createBlockContext(), $directive);

        self::assertInstanceOf(ImageNode::class, $result);
        self::assertSame('float-start', $directive->getOption('class')->getValue());
    }

    #[Test]
    public function processHandlesNoClassOption(): void
    {
        $directive = new Directive('', 'image', 'image.png');

        $this->logger->expects(self::never())->method('warning');

        $result = $this->subject->process($this->createBlockContext(), $directive);

        self::assertInstanceOf(ImageNode::class, $result);
    }

    #[Test]
    public function processHandlesNonStringClassValue(): void
    {
        $directive = new Directive('', 'image', 'image.png', [
            'class' => new DirectiveOption('class', true),
        ]);

        $this->logger->expects(self::never())->method('warning');

        $result = $this->subject->process($this->createBlockContext(), $directive);

        self::assertInstanceOf(ImageNode::class, $result);
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
