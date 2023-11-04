<?php

declare(strict_types=1);

namespace T3Docs\PhpDomain\Directives\Php;

use phpDocumentor\Guides\Nodes\CollectionNode;
use phpDocumentor\Guides\Nodes\Node;
use phpDocumentor\Guides\RestructuredText\Directives\SubDirective;
use phpDocumentor\Guides\RestructuredText\Parser\BlockContext;
use phpDocumentor\Guides\RestructuredText\Parser\Directive;
use T3Docs\PhpDomain\Nodes\PhpInterfaceNode;

final class InterfaceDirective extends SubDirective
{
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
        return new PhpInterfaceNode(
            $directive->getData(),
            $collectionNode->getChildren(),
            null,
            [],
            [],
        );
    }
}
