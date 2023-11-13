<?php

declare(strict_types=1);

namespace T3Docs\PhpDomain\Directives\Php;

use phpDocumentor\Guides\Nodes\CollectionNode;
use phpDocumentor\Guides\Nodes\Node;
use phpDocumentor\Guides\ReferenceResolvers\AnchorReducer;
use phpDocumentor\Guides\RestructuredText\Directives\SubDirective;
use phpDocumentor\Guides\RestructuredText\Parser\BlockContext;
use phpDocumentor\Guides\RestructuredText\Parser\Directive;
use phpDocumentor\Guides\RestructuredText\Parser\Productions\Rule;
use phpDocumentor\Guides\RestructuredText\TextRoles\GenericLinkProvider;
use T3Docs\PhpDomain\Nodes\PhpMethodNode;
use T3Docs\PhpDomain\PhpDomain\MethodNameService;

final class MethodDirective extends SubDirective
{
    public function __construct(
        Rule $startingRule,
        GenericLinkProvider $genericLinkProvider,
        private readonly MethodNameService $methodNameService,
        private readonly AnchorReducer $anchorReducer,
    ) {
        parent::__construct($startingRule);
        $genericLinkProvider->addGenericLink($this->getName(), $this->getName());
    }

    public function getName(): string
    {
        return 'php:method';
    }

    protected function processSub(
        BlockContext $blockContext,
        CollectionNode $collectionNode,
        Directive $directive,
    ): Node|null {
        $name = $this->methodNameService->getMethodName(trim($directive->getData()));
        $id = $this->anchorReducer->reduceAnchor($name->toString());

        $methodNode = new PhpMethodNode(
            $id,
            $name,
            $collectionNode->getChildren(),
        );
        return $methodNode;
    }
}
