<?php

namespace T3Docs\PhpDomain\Nodes;

use phpDocumentor\Guides\Nodes\AbstractNode;

/**
 * Stores data on PHP namespaces
 * @extends AbstractNode<string>
 */
class FullyQualifiedNameNode extends AbstractNode
{
    public function __construct(
        private readonly string $name,
        private readonly PhpNamespaceNode|null $namespaceNode,
    ) {
        $this->value = $name;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getNamespaceNode(): ?PhpNamespaceNode
    {
        return $this->namespaceNode;
    }

    public function toString(): string
    {
        if ($this->namespaceNode === null) {
            return $this->name;
        }
        return $this->namespaceNode->toString() . '\\' . $this->name;
    }
}
