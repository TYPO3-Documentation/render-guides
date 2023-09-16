<?php

namespace T3Docs\Typo3DocsTheme\Nodes;

use phpDocumentor\Guides\Nodes\InlineCompoundNode;
use phpDocumentor\Guides\Nodes\Node;
use phpDocumentor\Guides\RestructuredText\Nodes\GeneralDirectiveNode;

final class GroupTabNode extends GeneralDirectiveNode
{
    private string $key;

    /** @param list<Node> $value */
    public function __construct(
        protected readonly string $name,
        protected readonly string $plainContent,
        protected readonly InlineCompoundNode $content,
        array $value = [],
    ) {
        parent::__construct($name, $plainContent, $content, $value);
        $this->key = strtolower($plainContent);
        $this->key = strval(preg_replace('/^[a-zA-Z0-9-_]/', '', $this->key));
    }

    public function getKey(): string
    {
        return $this->key;
    }
}
