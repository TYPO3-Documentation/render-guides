<?php

namespace T3Docs\Typo3DocsTheme\Nodes;

use phpDocumentor\Guides\Nodes\InlineCompoundNode;
use phpDocumentor\Guides\Nodes\Node;
use phpDocumentor\Guides\RestructuredText\Nodes\GeneralDirectiveNode;

final class GlossaryNode extends GeneralDirectiveNode
{
    /**
     * @param Node[] $value
     * @param array<string, array<string, Node>> $entries
     */
    public function __construct(
        protected readonly string $plainContent,
        protected readonly InlineCompoundNode $content,
        array $value = [],
        protected array $entries = [],
    ) {
        parent::__construct('glossary', $plainContent, $content, array_values($value));
    }

    /**
     * @return array<string, array<string, Node>>
     */
    public function getEntries(): array
    {
        return $this->entries;
    }
}
