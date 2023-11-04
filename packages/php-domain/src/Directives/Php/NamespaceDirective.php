<?php

declare(strict_types=1);

namespace T3Docs\PhpDomain\Directives\Php;

use phpDocumentor\Guides\RestructuredText\Directives\ActionDirective;
use phpDocumentor\Guides\RestructuredText\Parser\BlockContext;
use phpDocumentor\Guides\RestructuredText\Parser\Directive;
use T3Docs\PhpDomain\Nodes\PhpNamespaceNode;
use T3Docs\PhpDomain\PhpDomain\NamespaceRepository;

final class NamespaceDirective extends ActionDirective
{
    public function __construct(
        private readonly NamespaceRepository $namespaceRepository
    ) {}

    public function getName(): string
    {
        return 'php:namespace';
    }

    public function processAction(BlockContext $blockContext, Directive $directive): void
    {
        $name = trim(trim($directive->getData()), '\\');
        if ($name === '') {
            $this->namespaceRepository->setCurrentNamespace(null);
            return;
        }
        $this->namespaceRepository->setCurrentNamespace(new PhpNamespaceNode($name));
    }
}
