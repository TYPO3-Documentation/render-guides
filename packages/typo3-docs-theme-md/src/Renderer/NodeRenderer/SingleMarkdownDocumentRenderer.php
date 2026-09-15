<?php

declare(strict_types=1);

namespace T3Docs\Typo3DocsThemeMd\Renderer\NodeRenderer;

use phpDocumentor\Guides\NodeRenderers\NodeRenderer;
use phpDocumentor\Guides\Nodes\DocumentNode;
use phpDocumentor\Guides\Nodes\Node;
use phpDocumentor\Guides\RenderContext;
use phpDocumentor\Guides\TemplateRenderer;

/**
 * Renders one document as part of the single Markdown file.
 *
 * The per-file renderer writes YAML front matter before the content, which is
 * what a standalone file needs and what a concatenated one must not repeat:
 * front matter is only front matter at the top of a file. This renderer emits
 * the body alone; the surrounding template writes the single front matter block
 * for the project.
 *
 * @implements NodeRenderer<DocumentNode>
 */
final class SingleMarkdownDocumentRenderer implements NodeRenderer
{
    private string $template = 'structure/singledocument.md.twig';

    public function __construct(
        private readonly TemplateRenderer $renderer,
    ) {}

    public function supports(string $nodeFqcn): bool
    {
        return DocumentNode::class === $nodeFqcn;
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
