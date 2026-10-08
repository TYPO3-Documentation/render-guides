<?php

declare(strict_types=1);

namespace T3Docs\Typo3DocsTheme\Tests\Unit\Directives;

use phpDocumentor\Guides\Compiler\CompilerContextInterface;
use phpDocumentor\Guides\Nodes\ImageNode;
use phpDocumentor\Guides\ReferenceResolvers\DocumentNameResolverInterface;
use phpDocumentor\Guides\RestructuredText\Nodes\DirectiveNode;
use phpDocumentor\Guides\RestructuredText\Nodes\DirectiveSourceLocation;
use phpDocumentor\Guides\RestructuredText\Parser\Directive;
use phpDocumentor\Guides\RestructuredText\Parser\DirectiveOption;
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
    public function createNodeRewritesLegacyFloatLeftClass(): void
    {
        $directive = new Directive('', 'image', 'image.png', [
            'class' => new DirectiveOption('class', 'float-left'),
        ]);

        $this->logger->expects(self::once())
            ->method('warning')
            ->with(self::stringContains('deprecated'));

        $result = $this->subject->createNode($this->createDirectiveNode($directive), $this->createMock(CompilerContextInterface::class));

        self::assertInstanceOf(ImageNode::class, $result);
        self::assertSame('float-start', $directive->getOption('class')->getValue());
    }

    #[Test]
    public function createNodeRewritesLegacyFloatRightClass(): void
    {
        $directive = new Directive('', 'image', 'image.png', [
            'class' => new DirectiveOption('class', 'float-right'),
        ]);

        $this->logger->expects(self::once())
            ->method('warning')
            ->with(self::stringContains('deprecated'));

        $result = $this->subject->createNode($this->createDirectiveNode($directive), $this->createMock(CompilerContextInterface::class));

        self::assertInstanceOf(ImageNode::class, $result);
        self::assertSame('float-end', $directive->getOption('class')->getValue());
    }

    #[Test]
    public function createNodeRewritesLegacyClassWithOtherClasses(): void
    {
        $directive = new Directive('', 'image', 'image.png', [
            'class' => new DirectiveOption('class', 'with-shadow float-left'),
        ]);

        $this->logger->expects(self::once())->method('warning');

        $result = $this->subject->createNode($this->createDirectiveNode($directive), $this->createMock(CompilerContextInterface::class));

        self::assertInstanceOf(ImageNode::class, $result);
        self::assertSame('with-shadow float-start', $directive->getOption('class')->getValue());
    }

    #[Test]
    public function createNodeDoesNotRewriteModernClasses(): void
    {
        $directive = new Directive('', 'image', 'image.png', [
            'class' => new DirectiveOption('class', 'float-start'),
        ]);

        $this->logger->expects(self::never())->method('warning');

        $result = $this->subject->createNode($this->createDirectiveNode($directive), $this->createMock(CompilerContextInterface::class));

        self::assertInstanceOf(ImageNode::class, $result);
        self::assertSame('float-start', $directive->getOption('class')->getValue());
    }

    #[Test]
    public function createNodeHandlesNoClassOption(): void
    {
        $directive = new Directive('', 'image', 'image.png');

        $this->logger->expects(self::never())->method('warning');

        $result = $this->subject->createNode($this->createDirectiveNode($directive), $this->createMock(CompilerContextInterface::class));

        self::assertInstanceOf(ImageNode::class, $result);
    }

    #[Test]
    public function createNodeHandlesNonStringClassValue(): void
    {
        $directive = new Directive('', 'image', 'image.png', [
            'class' => new DirectiveOption('class', true),
        ]);

        $this->logger->expects(self::never())->method('warning');

        $result = $this->subject->createNode($this->createDirectiveNode($directive), $this->createMock(CompilerContextInterface::class));

        self::assertInstanceOf(ImageNode::class, $result);
    }

    /** The node the parser leaves for a directive, which is turned into its node while compiling. */
    private function createDirectiveNode(Directive $directive): DirectiveNode
    {
        $directiveNode = new DirectiveNode($directive);
        $directiveNode->setSourceLocation(DirectiveSourceLocation::fromLoggerInformation(['rst-file' => 'test.rst'], '/test'));

        return $directiveNode;
    }
}
