<?php

declare(strict_types=1);

namespace T3Docs\Typo3DocsThemeMd\Renderer;

use phpDocumentor\Guides\NodeRenderers\NodeRenderer;
use phpDocumentor\Guides\NodeRenderers\NodeRendererFactory;
use phpDocumentor\Guides\NodeRenderers\NodeRendererFactoryAware;
use phpDocumentor\Guides\Nodes\Inline\InlineNodeInterface;
use phpDocumentor\Guides\Nodes\Node;
use phpDocumentor\Guides\RenderContext;

use function in_array;
use function is_callable;
use function is_string;
use function preg_replace;
use function str_replace;
use function strrpos;
use function substr;

/**
 * Marks nodes that have no Markdown template yet.
 *
 * It decorates the format's default renderer -- the slot that means "nothing
 * else matched" -- and leaves that renderer's output in place, so no content
 * is lost. It only prepends an HTML comment naming what was not rendered.
 *
 * Without this, a directive we have not covered yet disappears silently: the
 * page looks complete and the gap is invisible. An HTML comment is inert in
 * every Markdown renderer, so the marker costs nothing to a reader while
 * making the remaining work greppable.
 *
 * @implements NodeRenderer<Node>
 */
final class UnsupportedNodeRenderer implements NodeRenderer, NodeRendererFactoryAware
{
    /** @param NodeRenderer<Node> $inner */
    public function __construct(
        private readonly NodeRenderer $inner,
    ) {}

    public function setNodeRendererFactory(NodeRendererFactory $nodeRendererFactory): void
    {
        if (!$this->inner instanceof NodeRendererFactoryAware) {
            return;
        }

        $this->inner->setNodeRendererFactory($nodeRendererFactory);
    }

    public function supports(string $nodeFqcn): bool
    {
        return $this->inner->supports($nodeFqcn);
    }

    /**
     * Structural nodes that only hold children. The default renderer walks
     * into them and loses nothing, so marking them would bury the real gaps:
     * they account for the bulk of the fallbacks in a typical manual.
     */
    private const TRANSPARENT = [
        'Collection',
        'Container',
        'DefinitionListItem',
        'FieldListItem',
    ];

    public function render(Node $node, RenderContext $renderContext): string
    {
        $rendered = $this->inner->render($node, $renderContext);
        $name = $this->describe($node);

        if (in_array($name, self::TRANSPARENT, true)) {
            return $rendered;
        }

        $marker = '<!-- TODO: no Markdown rendering for "' . $name . '" -->';

        // An inline node sits inside a paragraph, a table cell or a list item,
        // where a blank line would break the construct around it.
        if ($node instanceof InlineNodeInterface) {
            return $marker . $rendered;
        }

        return $marker . "\n\n" . $rendered;
    }

    /**
     * Prefer the directive name an author would recognise, and fall back to the
     * node class when the node does not carry one.
     */
    private function describe(Node $node): string
    {
        foreach (['getName', 'getDirective', 'getType'] as $method) {
            $callable = [$node, $method];
            if (!is_callable($callable)) {
                continue;
            }

            $name = $callable();
            if (is_string($name) && $name !== '') {
                return $this->sanitize($name);
            }
        }

        $class = $node::class;
        $shortName = strrpos($class, '\\') === false ? $class : substr($class, strrpos($class, '\\') + 1);

        return $this->sanitize((string) preg_replace('/Node$/', '', $shortName));
    }

    /** Keep the comment a comment: "--" would end it early. */
    private function sanitize(string $value): string
    {
        return str_replace(['--', '>'], ['- -', ''], $value);
    }
}
