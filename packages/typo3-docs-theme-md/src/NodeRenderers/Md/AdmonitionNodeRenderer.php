<?php

declare(strict_types=1);

namespace T3Docs\Typo3DocsThemeMd\NodeRenderers\Md;

use InvalidArgumentException;
use phpDocumentor\Guides\NodeRenderers\NodeRenderer;
use phpDocumentor\Guides\Nodes\AdmonitionNode;
use phpDocumentor\Guides\Nodes\Node;
use phpDocumentor\Guides\RenderContext;
use phpDocumentor\Guides\TemplateRenderer;

use function is_a;

/**
 * Admonitions do not go through the node-to-template map: the HTML pipeline
 * renders them with a dedicated renderer that passes the name and title
 * separately, so the Markdown output needs its own.
 *
 * @implements NodeRenderer<AdmonitionNode>
 */
final class AdmonitionNodeRenderer implements NodeRenderer
{
    public function __construct(
        private readonly TemplateRenderer $renderer,
    ) {}

    public function supports(string $nodeFqcn): bool
    {
        return $nodeFqcn === AdmonitionNode::class || is_a($nodeFqcn, AdmonitionNode::class, true);
    }

    public function render(Node $node, RenderContext $renderContext): string
    {
        if ($node instanceof AdmonitionNode === false) {
            throw new InvalidArgumentException('Node must be an instance of ' . AdmonitionNode::class);
        }

        return $this->renderer->renderTemplate(
            $renderContext,
            'body/admonition.md.twig',
            [
                'name' => $node->getName(),
                'text' => $node->getText(),
                'title' => $node->getTitle(),
                'isTitled' => $node->isTitled(),
                'node' => $node->getValue(),
            ],
        );
    }
}
