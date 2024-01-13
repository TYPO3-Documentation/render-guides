<?php

declare(strict_types=1);

namespace T3Docs\Typo3DocsTheme\Renderer;

use phpDocumentor\Guides\Graphs\Renderer\DiagramRenderer;
use phpDocumentor\Guides\Graphs\Renderer\PlantumlServerRenderer;

final class DecoratingPlantumlRenderer implements DiagramRenderer
{
    private bool $disabled = false;


    public function __construct(private readonly PlantumlServerRenderer $innerRenderer) {}

    public function render(string $diagram): string|null
    {
        if ($this->disabled) {
            return 'The PlantUML renderer is not available in test mode.';
        }
        return $this->innerRenderer->render($diagram);
    }

    public function isDisabled(): bool
    {
        return $this->disabled;
    }

    public function setDisabled(bool $disabled): void
    {
        $this->disabled = $disabled;
    }
}
