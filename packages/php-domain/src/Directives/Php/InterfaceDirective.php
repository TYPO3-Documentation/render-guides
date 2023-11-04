<?php

declare(strict_types=1);

namespace T3Docs\PhpDomain\Directives\Php;

use phpDocumentor\Guides\Nodes\CollectionNode;
use phpDocumentor\Guides\Nodes\Node;
use phpDocumentor\Guides\RestructuredText\Directives\SubDirective;
use phpDocumentor\Guides\RestructuredText\Parser\BlockContext;
use phpDocumentor\Guides\RestructuredText\Parser\Directive;
use phpDocumentor\Guides\RestructuredText\Parser\Productions\Rule;
use T3Docs\PhpDomain\Nodes\PhpInterfaceNode;
use T3Docs\PhpDomain\PhpDomain\FullyQualifiedNameService;

final class InterfaceDirective extends SubDirective
{
    public function __construct(
        Rule $startingRule,
        private readonly FullyQualifiedNameService $fullyQualifiedNameService
    ) {
        parent::__construct($startingRule);
    }

    public function getName(): string
    {
        return 'php:interface';
    }

    /** {@inheritDoc}
     *
     * @param Directive $directive
     */
    protected function processSub(
        BlockContext $blockContext,
        CollectionNode $collectionNode,
        Directive $directive,
    ): Node|null {
        $name = trim($directive->getData());
        $fqn = $this->fullyQualifiedNameService->getFullyQualifiedName($name);

        $interfaceNode = new PhpInterfaceNode(
            $fqn,
            $collectionNode->getChildren(),
            null,
            [],
            [],
        );
        return $interfaceNode;
    }
}
