<?php

declare(strict_types=1);

namespace T3Docs\Typo3DocsTheme\Tests\Unit\Renderer;

use phpDocumentor\Guides\Graphs\Renderer\DiagramRenderer;
use phpDocumentor\Guides\RenderContext;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use T3Docs\Typo3DocsTheme\Renderer\DecoratingPlantumlBinaryRenderer;

use function is_dir;
use function rmdir;
use function sys_get_temp_dir;

final class DecoratingPlantumlBinaryRendererTest extends TestCase
{
    private const TEMP_SUBDIRECTORY = '/phpdocumentor';

    #[Test]
    public function renderCreatesTempDirectoryWhenMissing(): void
    {
        $tempDir = sys_get_temp_dir() . self::TEMP_SUBDIRECTORY;

        // Remove the directory if it exists to test creation
        if (is_dir($tempDir)) {
            @rmdir($tempDir);
        }

        // Skip if we can't remove it (contains files from other processes)
        if (is_dir($tempDir)) {
            self::markTestSkipped('Cannot remove temp directory - it contains files from other processes');
        }

        $innerRenderer = $this->createMock(DiagramRenderer::class);
        $innerRenderer->method('render')->willReturn('<svg></svg>');

        $renderContext = $this->createMock(RenderContext::class);

        $decorator = new DecoratingPlantumlBinaryRenderer($innerRenderer);
        $decorator->render($renderContext, 'A -> B');

        self::assertDirectoryExists($tempDir);

        // Clean up
        @rmdir($tempDir);
    }

    #[Test]
    public function renderDelegatesToInnerRenderer(): void
    {
        $expectedResult = '<svg>diagram</svg>';
        $diagram = 'A -> B';

        $renderContext = $this->createMock(RenderContext::class);

        $innerRenderer = $this->createMock(DiagramRenderer::class);
        $innerRenderer->expects(self::once())
            ->method('render')
            ->with($renderContext, $diagram)
            ->willReturn($expectedResult);

        $decorator = new DecoratingPlantumlBinaryRenderer($innerRenderer);
        $result = $decorator->render($renderContext, $diagram);

        self::assertSame($expectedResult, $result);
    }
}
