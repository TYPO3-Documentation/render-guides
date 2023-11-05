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
        array $value = [],
    ) {
        $key = strtolower($plainContent);
        $key = (string)(preg_replace('/^[a-zA-Z0-9-_]/', '', $key));
        parent::__construct($name, $plainContent, $content, $key, false, $value);
    }
}
