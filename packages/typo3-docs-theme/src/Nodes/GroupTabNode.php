<?php

namespace T3Docs\Typo3DocsTheme\Nodes;

use phpDocumentor\Guides\Bootstrap\Nodes\AbstractTabNode;
use phpDocumentor\Guides\Nodes\InlineCompoundNode;
use phpDocumentor\Guides\Nodes\Node;

final class GroupTabNode extends AbstractTabNode
{
    /** @param list<Node> $value */
    public function __construct(
        string $name,
        string $plainContent,
        InlineCompoundNode $content,
        string $key,
        array $value = [],
    ) {
        parent::__construct($name, $plainContent, $content, $key, false, $value);
    }
}
