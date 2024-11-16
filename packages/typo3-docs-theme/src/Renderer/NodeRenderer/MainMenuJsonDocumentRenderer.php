<?php

namespace T3Docs\Typo3DocsTheme\Renderer\NodeRenderer;

use phpDocumentor\Guides\NodeRenderers\NodeRenderer;
use phpDocumentor\Guides\Nodes\CollectionNode;
use phpDocumentor\Guides\Nodes\CompoundNode;
use phpDocumentor\Guides\Nodes\DocumentNode;
use phpDocumentor\Guides\Nodes\Inline\LinkInlineNode;
use phpDocumentor\Guides\Nodes\ListItemNode;
use phpDocumentor\Guides\Nodes\ListNode;
use phpDocumentor\Guides\Nodes\Node;
use phpDocumentor\Guides\ReferenceResolvers\DelegatingReferenceResolver;
use phpDocumentor\Guides\ReferenceResolvers\Messages;
use phpDocumentor\Guides\RenderContext;
use T3Docs\Typo3DocsTheme\Nodes\MainMenuJsonNode;

/** @implements NodeRenderer<Node> */
class MainMenuJsonDocumentRenderer implements NodeRenderer
{
    public function __construct(
        private readonly DelegatingReferenceResolver $delegatingReferenceResolver,
    ) {}

    public function supports(string $nodeFqcn): bool
    {
        return DocumentNode::class === $nodeFqcn;
    }

    public function render(Node $node, RenderContext $renderContext): string
    {
        $result = '';
        if ($node instanceof DocumentNode) {
            foreach ($node->getChildren() as $childNode) {
                $result .= $this->render($childNode, $renderContext);
            }
        }
        if ($node instanceof CollectionNode) {
            foreach ($node->getChildren() as $childNode) {
                $result .= $this->render($childNode, $renderContext);
            }
        }
        if (!$node instanceof MainMenuJsonNode) {
            return $result;
        }
        return $result . $this->renderMainMenu($node, $renderContext);
    }

    private function renderMainMenu(MainMenuJsonNode $node, RenderContext $renderContext): string
    {
        $stringResult = '';
        $result = [];
        foreach ($node->getChildren() as $listNode) {
            $this->renderSubEntry($listNode, $renderContext, $result);
        }
        return $stringResult . json_encode($result, JSON_PRETTY_PRINT);
    }

    /**
     * @param array<mixed> $result
     */
    public function renderSubEntry(Node $node, RenderContext $renderContext, array &$result): void
    {
        if ($node instanceof ListNode) {
            foreach ($node->getChildren() as $listItemNode) {
                if (!$listItemNode instanceof ListItemNode) {
                    continue;
                }
                $menuEntry = [];
                foreach ($listItemNode->getChildren() as $childNode) {
                    if ($childNode instanceof ListNode) {
                        $this->renderSubEntryList($menuEntry, $childNode, $renderContext);
                    } elseif ($childNode instanceof CompoundNode) {
                        $this->renderMenuEntry($menuEntry, $childNode, $renderContext);
                    }
                }
                $result[] = $menuEntry;
            }
        } elseif ($node instanceof CompoundNode) {
            foreach ($node->getChildren() as $childNode) {
                $this->renderSubEntry($childNode, $renderContext, $result);
            }
        }
    }

    /**
     * @param array<mixed> $menuEntry
     */
    private function renderMenuEntry(array &$menuEntry, Node $node, RenderContext $renderContext): void
    {
        if ($node instanceof LinkInlineNode) {
            $this->delegatingReferenceResolver->resolve($node, $renderContext, new Messages());
            $url = $node->getUrl();
            $parsedUrl = parse_url($url);
            if (!isset($parsedUrl['scheme'])) {
                $url = 'https://docs.typo3.org/' . $url;
            }
            $menuEntry['name'] = $node->getValue();
            $menuEntry['href'] = $url;
            return;
        }

        if ($node instanceof CompoundNode) {
            foreach ($node->getChildren() as $childNode) {
                $this->renderMenuEntry($menuEntry, $childNode, $renderContext);
            }
            return;
        }
    }
    /**
     * @param array<mixed> $menuEntry
     */
    private function renderSubEntryList(array &$menuEntry, ListNode $listNode, RenderContext $renderContext): void
    {
        $subListItems = [];
        $this->renderSubEntry($listNode, $renderContext, $subListItems);
        $menuEntry['children'] = $subListItems;
    }

}
