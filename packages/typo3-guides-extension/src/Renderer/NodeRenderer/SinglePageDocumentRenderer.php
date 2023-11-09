<?php

namespace T3Docs\GuidesExtension\Renderer\NodeRenderer;

use phpDocumentor\Guides\NodeRenderers\NodeRenderer;
use phpDocumentor\Guides\Nodes\DocumentNode;
use phpDocumentor\Guides\Nodes\Node;
use phpDocumentor\Guides\RenderContext;
use phpDocumentor\Guides\TemplateRenderer;

/** @implements NodeRenderer<DocumentNode> */
class SinglePageDocumentRenderer implements NodeRenderer
{
    private string $template = 'structure/singledocument.html.twig';

    public function __construct(
        private readonly TemplateRenderer $renderer,
    ) {}
    public function supports(Node $node): bool
    {
        return $node instanceof DocumentNode;
    }

    public function render(Node $node, RenderContext $renderContext): string
    {
        return $this->renderer->renderTemplate(
            $renderContext->withDocument($node),
            $this->template,
            [
                'node' => $node,
            ],
        );
    }
}
