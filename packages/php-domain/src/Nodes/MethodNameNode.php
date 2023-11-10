<?php

namespace T3Docs\PhpDomain\Nodes;

use phpDocumentor\Guides\Nodes\AbstractNode;

/**
 * Stores data on PHP namespaces
 * @extends AbstractNode<string>
 */
class MethodNameNode extends AbstractNode
{
    /**
     * @param list<string> $params
     */
    public function __construct(
        private readonly string $name,
        private readonly array $params,
        private readonly string|null $return,
    ) {
        $this->value = $name;
    }

    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return list<string>
     */
    public function getParams(): array
    {
        return $this->params;
    }

    public function getReturn(): ?string
    {
        return $this->return;
    }

    public function toString(): string
    {
        return $this->name;
    }
}
