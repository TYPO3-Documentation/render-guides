<?php

namespace T3Docs\Typo3DocsTheme\Nodes;

use phpDocumentor\Guides\Nodes\InlineCompoundNode;
use phpDocumentor\Guides\Nodes\Node;
use phpDocumentor\Guides\RestructuredText\Nodes\ConfvalNode;
use phpDocumentor\Guides\RestructuredText\Nodes\GeneralDirectiveNode;

final class ConfvalMenuNode extends GeneralDirectiveNode
{
    /**
     * @param list<Node> $value
     * @param Node[] $value
     * @param ConfvalNode[] $confvals
     * @param string[] $fields
     * @param string[] $exclude
     */
    public function __construct(
        protected readonly string $plainContent,
        protected readonly InlineCompoundNode $content,
        array $value = [],
        private array $confvals = [],
        private readonly array $fields = [],
        private readonly string $display = 'tree',
        private readonly bool $excludeNoindex = false,
        private readonly array $exclude = []
    ) {
        parent::__construct('confval-menu', $plainContent, $content, $value);
    }

    /**
     * @return ConfvalNode[]
     */
    public function getConfvals(): array
    {
        return $this->confvals;
    }

    /**
     * @param ConfvalNode[] $confvals
     */
    public function setConfvals(array $confvals): void
    {
        $this->confvals = $confvals;
    }

    /**
     * @return string[]
     */
    public function getFields(): array
    {
        return $this->fields;
    }

    public function getDisplay(): string
    {
        return $this->display;
    }

    public function isExcludeNoindex(): bool
    {
        return $this->excludeNoindex;
    }

    /**
     * @return string[]
     */
    public function getExclude(): array
    {
        return $this->exclude;
    }
}
