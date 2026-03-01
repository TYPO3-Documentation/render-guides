<?php

namespace T3Docs\Typo3DocsTheme\Nodes\DirectoryTree;

use phpDocumentor\Guides\Nodes\CompoundNode;
use phpDocumentor\Guides\Nodes\Node;

/** @extends CompoundNode<Node> */
final class DirectoryTreeListItemNode extends CompoundNode
{
    /**
     * @param Node[] $items
     * @param string $name
     * @param DirectoryTreeListNode[] $subLists
     */
    public function __construct(
        array $items,
        private readonly string $name,
        private readonly array $subLists,
    ) {
        parent::__construct(array_values($items));
    }

    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return DirectoryTreeListNode[]
     */
    public function getSubLists(): array
    {
        return $this->subLists;
    }
}
