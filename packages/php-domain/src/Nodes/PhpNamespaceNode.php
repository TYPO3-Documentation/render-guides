<?php

declare(strict_types=1);

namespace T3Docs\PhpDomain\Nodes;

use phpDocumentor\Guides\Nodes\AbstractNode;

/**
 * Stores data on PHP namespaces
 * @extends AbstractNode<string>
 */
final class PhpNamespaceNode extends AbstractNode
{
    public function __construct(
        private readonly string $name,
    ) {
        $this->value = $name;
    }

    public function getName(): string
    {
        return $this->name;
    }
}
