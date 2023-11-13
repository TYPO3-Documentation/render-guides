<?php

declare(strict_types=1);

namespace T3Docs\PhpDomain\Nodes;

use phpDocumentor\Guides\Nodes\Node;

final class PhpMethodNode extends PhpMemberNode
{
    private const TYPE = 'method';
    /**
     * @param Node[] $value
     */
    public function __construct(
        string $id,
        private readonly MethodNameNode $methodName,
        array $value = [],
    ) {
        parent::__construct($id, self::TYPE, $methodName->toString(), $value);
    }

    public function getMethodName(): MethodNameNode
    {
        return $this->methodName;
    }
}
