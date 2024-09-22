<?php

declare(strict_types=1);

namespace T3Docs\Typo3DocsTheme\Directives;

use phpDocumentor\Guides\Nodes\CollectionNode;
use phpDocumentor\Guides\Nodes\InlineCompoundNode;
use phpDocumentor\Guides\Nodes\Node;
use phpDocumentor\Guides\RestructuredText\Directives\SubDirective;
use phpDocumentor\Guides\RestructuredText\Parser\BlockContext;
use phpDocumentor\Guides\RestructuredText\Parser\Directive;
use phpDocumentor\Guides\RestructuredText\Parser\Productions\Rule;
use T3Docs\Typo3DocsTheme\Nodes\MainMenuJsonNode;

/**
 * Used in the main documentation page docs.typo3.org to configure
 * the main menu docs.typo3.org/mainMenu.json.
 *
 * To be used together with the MainMenuJsonRenderer and the template
 * :template: mainMenu.json
 */
class MainMenuJsonDirective extends SubDirective
{
    public function __construct(
        Rule $startingRule,
    ) {
        parent::__construct($startingRule);
    }

    public function getName(): string
    {
        return 'main-menu-json';
    }

    protected function processSub(
        BlockContext   $blockContext,
        CollectionNode $collectionNode,
        Directive      $directive,
    ): Node|null {
        return new MainMenuJsonNode(
            $directive->getData(),
            $directive->getDataNode() ?? new InlineCompoundNode(),
            $collectionNode->getChildren(),
        );
    }
}
