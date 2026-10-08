<?php

namespace T3Docs\Typo3DocsTheme\Nodes\DirectoryTree;

use T3Docs\Typo3DocsTheme\Nodes\CompiledPropertiesTrait;
use phpDocumentor\Guides\Nodes\CompoundNode;
use phpDocumentor\Guides\Nodes\Node;

/** @extends CompoundNode<Node> */
final class DirectoryTreeListItemNode extends CompoundNode
{
    use CompiledPropertiesTrait;

    /**
     * @param Node[] $items
     * @param string $name
     * @param DirectoryTreeListNode[] $subLists
     */
    public function __construct(
        array $items,
        private readonly string $name,
        private array $subLists,
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

    /** @return list<string> */
    protected function compiledProperties(): array
    {
        return ['subLists'];
    }
}
