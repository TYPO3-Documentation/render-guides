<?php

declare(strict_types=1);

namespace T3Docs\PhpDomain\Nodes;

use phpDocumentor\Guides\Nodes\CompoundNode;
use phpDocumentor\Guides\Nodes\Node;

/**
 * Stores data on constants, methods and properties
 *
 * @extends CompoundNode<Node>
 */
abstract class PhpMemberNode extends CompoundNode
{
    public function __construct(
        private readonly string $id,
        private readonly string $type,
        private readonly string $name,
        array $value = [],
    ) {
        parent::__construct($value);
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getId(): string
    {
        return $this->id;
    }
}
