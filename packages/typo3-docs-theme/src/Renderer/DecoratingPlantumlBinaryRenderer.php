<?php

declare(strict_types=1);

namespace T3Docs\Typo3DocsTheme\Renderer;

use phpDocumentor\Guides\Graphs\Renderer\DiagramRenderer;
use phpDocumentor\Guides\Graphs\Renderer\PlantumlRenderer;
use phpDocumentor\Guides\RenderContext;

use function is_dir;
use function mkdir;
use function sys_get_temp_dir;

/**
 * Decorator for PlantumlRenderer that ensures the temp directory exists.
 *
 * The upstream PlantumlRenderer uses tempnam() with a subdirectory that may not exist,
 * which triggers an E_NOTICE. This decorator creates the directory before rendering.
 *
 * @see https://github.com/TYPO3-Documentation/render-guides/pull/1099
 */
final class DecoratingPlantumlBinaryRenderer implements DiagramRenderer
{
    private const TEMP_SUBDIRECTORY = '/phpdocumentor';

    public function __construct(private readonly PlantumlRenderer $innerRenderer) {}

    public function render(RenderContext $renderContext, string $diagram): string|null
    {
        $this->ensureTempDirectoryExists();

        return $this->innerRenderer->render($renderContext, $diagram);
    }

    private function ensureTempDirectoryExists(): void
    {
        $tempDir = sys_get_temp_dir() . self::TEMP_SUBDIRECTORY;

        if (!is_dir($tempDir)) {
            mkdir($tempDir, 0o777, true);
        }
    }
}
