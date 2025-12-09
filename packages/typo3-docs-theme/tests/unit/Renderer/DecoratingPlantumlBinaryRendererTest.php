<?php

declare(strict_types=1);

namespace T3Docs\Typo3DocsTheme\Tests\Unit\Renderer;

use phpDocumentor\Guides\Graphs\Renderer\DiagramRenderer;
use phpDocumentor\Guides\RenderContext;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use T3Docs\Typo3DocsTheme\Renderer\DecoratingPlantumlBinaryRenderer;

use function file_exists;
use function is_dir;
use function rmdir;
use function set_error_handler;
use function sys_get_temp_dir;
use function tempnam;
use function unlink;

final class DecoratingPlantumlBinaryRendererTest extends TestCase
{
    private const TEMP_SUBDIRECTORY = '/phpdocumentor';

    /**
     * Canary test: Verifies that tempnam() still triggers E_NOTICE when directory doesn't exist.
     *
     * This test documents the upstream bug that DecoratingPlantumlBinaryRenderer works around.
     * When this test FAILS, the upstream library (phpDocumentor/guides-graphs) has likely
     * fixed the issue, and this decorator may no longer be needed.
     *
     * @see https://github.com/phpDocumentor/guides-graphs/issues/1
     * @see https://github.com/TYPO3-Documentation/render-guides/pull/1099
     */
    #[Test]
    public function tempnamStillTriggersNoticeWhenDirectoryMissing(): void
    {
        // Use a unique non-existent directory to avoid interference
        $nonExistentDir = sys_get_temp_dir() . '/phpdocumentor_canary_' . uniqid();

        $noticeTriggered = false;
        $previousHandler = set_error_handler(static function (int $errno, string $errstr) use (&$noticeTriggered): bool {
            if ($errno === E_NOTICE && str_contains($errstr, 'tempnam()')) {
                $noticeTriggered = true;
            }
            return false; // Let PHP handle it normally
        });

        try {
            $tempFile = @tempnam($nonExistentDir, 'canary_');

            // Clean up the temp file if it was created (in system temp dir)
            if ($tempFile !== false && file_exists($tempFile)) {
                unlink($tempFile);
            }
        } finally {
            set_error_handler($previousHandler);
        }

        self::assertTrue(
            $noticeTriggered,
            'Expected tempnam() to trigger E_NOTICE when directory does not exist. '
            . 'If this test fails, the upstream issue may be fixed and DecoratingPlantumlBinaryRenderer '
            . 'might no longer be needed. Check: https://github.com/phpDocumentor/guides-graphs/issues/1'
        );
    }

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
