<?php

declare(strict_types=1);

namespace T3Docs\PhpDomain\Nodes;

use phpDocumentor\Guides\Nodes\Node;

final class PhpInterfaceNode extends PhpComponentNode
{
    private const TYPE = 'interface';
    /**
     * @param list<PhpMemberNode> $members
     * @param list<string> $modifiers
     * @param list<Node> $value
     */
    public function __construct(
        string $name,
        array $value = [],
        PhpNamespaceNode|null $namespace = null,
        array $members = [],
        array $modifiers = [],
    ) {
        parent::__construct(self::TYPE, $name, $value, $namespace, $members, $modifiers);
    }
}
