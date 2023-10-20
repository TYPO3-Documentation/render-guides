<?php

namespace T3Docs\Typo3DocsTheme\Nodes;

use phpDocumentor\Guides\Nodes\InlineCompoundNode;
use phpDocumentor\Guides\Nodes\Node;
use phpDocumentor\Guides\RestructuredText\Nodes\GeneralDirectiveNode;

final class TabsNode extends GeneralDirectiveNode
{
    /** @var GroupTabNode[] $tabs */
    private array $tabs = [];

    /** @param list<Node> $value */
    public function __construct(
        protected readonly string $name,
        protected readonly string $plainContent,
        protected readonly InlineCompoundNode $content,
        array $value = [],
    ) {
        parent::__construct($name, $plainContent, $content, $value);
        foreach ($value as $child) {
            if ($child instanceof TabsNode) {
                $this->tabs[] = $child;
            }
        }
    }

    /**
     * @return GroupTabNode[]
     */
    public function getTabs(): array
    {
        return $this->tabs;
    }
}
