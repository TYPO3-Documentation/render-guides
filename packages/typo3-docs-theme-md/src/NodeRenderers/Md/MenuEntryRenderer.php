<?php

declare(strict_types=1);

namespace T3Docs\Typo3DocsThemeMd\NodeRenderers\Md;

use phpDocumentor\Guides\NodeRenderers\NodeRenderer;
use phpDocumentor\Guides\Nodes\Menu\MenuEntryNode;
use phpDocumentor\Guides\Nodes\Node;
use phpDocumentor\Guides\RenderContext;
use phpDocumentor\Guides\TemplateRenderer;
use phpDocumentor\Guides\Renderer\UrlGenerator\UrlGeneratorInterface;
use Webmozart\Assert\Assert;

use function is_a;

/** @implements NodeRenderer<MenuEntryNode> */
final class MenuEntryRenderer implements NodeRenderer
{
    public function __construct(
        private readonly TemplateRenderer $renderer,
        private readonly UrlGeneratorInterface $urlGenerator,
    ) {}

    public function supports(string $nodeFqcn): bool
    {
        return $nodeFqcn === MenuEntryNode::class || is_a($nodeFqcn, MenuEntryNode::class, true);
    }

    public function render(Node $node, RenderContext $renderContext): string
    {
        Assert::isInstanceOf($node, MenuEntryNode::class);

        $url = $this->urlGenerator->generateCanonicalOutputUrl(
            $renderContext,
            $node->getUrl(),
            $node->getValue()?->getId(),
        );

        return $this->renderer->renderTemplate(
            $renderContext,
            'body/menu/menu-item.md.twig',
            [
                'url' => $url,
                'node' => $node,
            ],
        );
    }
}
