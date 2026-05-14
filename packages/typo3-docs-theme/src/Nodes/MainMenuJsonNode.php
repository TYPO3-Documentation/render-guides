<?php

namespace T3Docs\Typo3DocsTheme\Nodes;

use phpDocumentor\Guides\Nodes\InlineCompoundNode;
use phpDocumentor\Guides\Nodes\Node;
use phpDocumentor\Guides\RestructuredText\Nodes\GeneralDirectiveNode;

final class MainMenuJsonNode extends GeneralDirectiveNode
{
    /**
 * @param Node[] $value
 */
    public function __construct(
        protected readonly string $plainContent,
        protected readonly InlineCompoundNode $content,
        array $value = [],
    ) {
        parent::__construct('main-menu-json', $plainContent, $content, array_values($value));
    }

}
