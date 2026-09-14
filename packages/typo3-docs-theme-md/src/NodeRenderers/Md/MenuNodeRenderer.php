<?php

declare(strict_types=1);

namespace T3Docs\Typo3DocsThemeMd\NodeRenderers\Md;

use phpDocumentor\Guides\NodeRenderers\NodeRenderer;
use phpDocumentor\Guides\Nodes\Menu\ContentMenuNode;
use phpDocumentor\Guides\Nodes\Menu\MenuNode;
use phpDocumentor\Guides\Nodes\Menu\TocNode;
use phpDocumentor\Guides\Nodes\Node;
use phpDocumentor\Guides\RenderContext;
use phpDocumentor\Guides\TemplateRenderer;
use Webmozart\Assert\Assert;

use function is_a;

/**
 * Renders a toctree as the list of links it is, rather than letting the
 * document contents of the entries be inlined into the page.
 *
 * A hidden toctree produces nothing, matching the HTML output: authors use
 * ":hidden:" to build the navigation without showing the list, but they also
 * write "the following pages exist:" above a visible one, where the list is
 * part of what the page says.
 *
 * @implements NodeRenderer<MenuNode>
 */
final class MenuNodeRenderer implements NodeRenderer
{
    public function __construct(
        private readonly TemplateRenderer $renderer,
    ) {}

    public function supports(string $nodeFqcn): bool
    {
        return $nodeFqcn === MenuNode::class || is_a($nodeFqcn, MenuNode::class, true);
    }

    public function render(Node $node, RenderContext $renderContext): string
    {
        Assert::isInstanceOf($node, MenuNode::class);

        if ($node->getOption('hidden', false)) {
            return '';
        }

        return $this->renderer->renderTemplate(
            $renderContext,
            $this->getTemplate($node),
            ['node' => $node],
        );
    }

    private function getTemplate(Node $node): string
    {
        if ($node instanceof TocNode) {
            return 'body/menu/table-of-content.md.twig';
        }

        if ($node instanceof ContentMenuNode) {
            return 'body/menu/content-menu.md.twig';
        }

        return 'body/menu/menu.md.twig';
    }
}
