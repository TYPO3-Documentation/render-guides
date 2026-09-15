<?php

declare(strict_types=1);

namespace T3Docs\Typo3DocsThemeMd\Renderer\NodeRenderer;

use phpDocumentor\Guides\NodeRenderers\NodeRenderer;
use phpDocumentor\Guides\NodeRenderers\NodeRendererFactory;
use phpDocumentor\Guides\NodeRenderers\NodeRendererFactoryAware;
use phpDocumentor\Guides\Nodes\Inline\InlineNode;
use phpDocumentor\Guides\Nodes\LinkTargetNode;
use phpDocumentor\Guides\Nodes\Menu\MenuEntryNode;
use phpDocumentor\Guides\Nodes\Node;
use phpDocumentor\Guides\Nodes\SectionNode;
use phpDocumentor\Guides\ReferenceResolvers\AnchorNormalizer;
use phpDocumentor\Guides\RenderContext;
use T3Docs\Typo3DocsThemeMd\Anchors\AddressableAnchors;

use function array_unique;
use function array_values;
use function is_string;
use function method_exists;

/**
 * Writes the anchor of a node before its Markdown, for the single-file output.
 *
 * A section is not the only thing a link can point at: a confval, a console
 * command, a glossary term, a ViewHelper argument or a PHP class is a target of
 * its own. The HTML output gives each of them an "id" attribute; Markdown has
 * no attributes, so the single file writes an empty HTML anchor instead.
 *
 * Every one of them is a LinkTargetNode, which is how they are recognised here
 * rather than by a list of classes: a node type added later is covered without
 * anybody remembering this file.
 *
 * @implements NodeRenderer<Node>
 */
final class AnchoringNodeRenderer implements NodeRenderer, NodeRendererFactoryAware
{
    private NodeRendererFactory $nodeRendererFactory;

    public function __construct(
        private readonly AnchorNormalizer $anchorNormalizer,
    ) {}

    public function setNodeRendererFactory(NodeRendererFactory $nodeRendererFactory): void
    {
        if (isset($this->nodeRendererFactory)) {
            return;
        }

        $this->nodeRendererFactory = $nodeRendererFactory;
    }

    public function supports(string $nodeFqcn): bool
    {
        return true;
    }

    public function render(Node $node, RenderContext $renderContext): string
    {
        $rendered = $this->nodeRendererFactory->get($node)->render($node, $renderContext);

        $anchors = $this->anchorsOf($node, $renderContext);
        if ($anchors === []) {
            return $rendered;
        }

        $prefix = '';
        foreach ($anchors as $anchor) {
            $prefix .= '<a id="' . $anchor . '"></a>' . "\n";
        }

        return $prefix . "\n" . $rendered;
    }

    /**
     * The anchors this node is reachable under, addressable ones only.
     *
     * Two forms are offered, because two exist. A confval is registered under
     * its prefixed anchor ("confval-backend-module-extensionname") while its id
     * is the bare name; a PHP class has an id and no prefix at all. Which of
     * them a link carries is not visible from here, so both are tried and
     * whichever is registered gets written. Usually that is one of the two.
     *
     * Three kinds of node are skipped although they carry one.
     *
     * A section is written by the section template instead, because it has two
     * candidate ids -- its label and its heading -- and both may be linked.
     * Writing it here as well would repeat the id.
     *
     * An inline node lives inside a paragraph, and an anchor is a block. Wedged
     * between two words it would break the line it sits in.
     *
     * A menu entry carries the id of the page it points at, not its own. A
     * table of contents would otherwise emit the anchor of every page it lists,
     * before those pages emit it themselves -- the id would be duplicated, and
     * every link to it would land in the table of contents rather than at the
     * page.
     *
     * Reduced the same way the link side reduces its target, or the two do not
     * meet.
     *
     * @return list<string>
     */
    private function anchorsOf(Node $node, RenderContext $renderContext): array
    {
        if ($node instanceof SectionNode || $node instanceof InlineNode || $node instanceof MenuEntryNode) {
            return [];
        }

        if (!$node instanceof LinkTargetNode) {
            return [];
        }

        $candidates = [$node->getId()];
        if (method_exists($node, 'getAnchor')) {
            $anchor = $node->getAnchor();
            if (is_string($anchor)) {
                $candidates[] = $anchor;
            }
        }

        $projectNode = $renderContext->getProjectNode();

        $anchors = [];
        foreach ($candidates as $candidate) {
            $anchor = $this->anchorNormalizer->reduceAnchor($candidate);
            if (!AddressableAnchors::isAddressable($projectNode, $anchor)) {
                continue;
            }

            $anchors[] = $anchor;
        }

        return array_values(array_unique($anchors));
    }
}
