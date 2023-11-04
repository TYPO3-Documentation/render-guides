<?php

declare(strict_types=1);

namespace T3Docs\PhpDomain\NodeRenderers;

use phpDocumentor\Guides\NodeRenderers\NodeRenderer;
use phpDocumentor\Guides\Nodes\Node;
use phpDocumentor\Guides\RenderContext;
use phpDocumentor\Guides\TemplateRenderer;

/** @implements NodeRenderer<Node> */
final class PhpNodeRenderer implements NodeRenderer
{
    /**
     * @param array<string, string> $templateMatching
     */
    public function __construct(
        private readonly TemplateRenderer $renderer,
        private readonly array $templateMatching
    ) {}

    public function render(Node $node, RenderContext $renderContext): string
    {
        foreach ($this->templateMatching as $class => $template) {
            if ($node instanceof $class) {
                return $this->renderer->renderTemplate($renderContext, $template, ['node' => $node]);
            }
        }
        throw new \Exception('No template found for node ' . $node::class);
    }

    public function supports(Node $node): bool
    {
        foreach ($this->templateMatching as $class => $template) {
            if ($node instanceof $class) {
                return true;
            }
        }
        return false;
    }
}
