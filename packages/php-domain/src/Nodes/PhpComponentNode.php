<?php

declare(strict_types=1);

namespace T3Docs\PhpDomain\Nodes;

use phpDocumentor\Guides\Nodes\CompoundNode;
use phpDocumentor\Guides\Nodes\Node;

/**
 * Stores data on PHP classes, interfaces and traits
 *
 * @extends CompoundNode<Node>
 */
abstract class PhpComponentNode extends CompoundNode
{
    /**
     * @param list<PhpMemberNode> $members
     * @param list<string> $modifiers
     * @param list<Node> $value
     */
    public function __construct(
        private readonly string $type,
        private readonly string $name,
        array $value = [],
        private PhpNamespaceNode|null $namespace = null,
        private array $members = [],
        private array $modifiers = [],
    ) {
        parent::__construct($value);
    }

    public function getNamespace(): ?PhpNamespaceNode
    {
        return $this->namespace;
    }

    public function setNamespace(?PhpNamespaceNode $namespace): void
    {
        $this->namespace = $namespace;
    }

    public function getMembers(): array
    {
        return $this->members;
    }

    public function setMembers(array $members): void
    {
        $this->members = $members;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getModifiers(): array
    {
        return $this->modifiers;
    }
}
