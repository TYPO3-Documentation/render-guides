<?php

namespace T3Docs\Typo3DocsTheme\Nodes\DirectoryTree;

use phpDocumentor\Guides\Nodes\CompoundNode;

/** @extends CompoundNode<DirectoryTreeListItemNode> */
final class DirectoryTreeListNode extends CompoundNode
{
    /**
     * @param DirectoryTreeListItemNode[] $items
     */
    public function __construct(array $items, private readonly string $name)
    {
        parent::__construct(array_values($items));
    }

    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return DirectoryTreeListItemNode[]
     */
    public function getChildren(): array
    {
        return $this->value;
    }
}
