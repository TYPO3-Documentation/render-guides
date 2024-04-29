<?php

namespace T3Docs\Typo3DocsTheme\Nodes;

use phpDocumentor\Guides\Nodes\InlineCompoundNode;
use phpDocumentor\Guides\Nodes\Node;
use phpDocumentor\Guides\RestructuredText\Nodes\GeneralDirectiveNode;

final class DirectoryTreeNode extends GeneralDirectiveNode
{
    /** @param list<Node> $value */
    public function __construct(
        protected readonly string $name,
        protected readonly string $plainContent,
        protected readonly InlineCompoundNode $content,
        array $value = [],
        private readonly string $id = '',
        private readonly int $level = PHP_INT_MAX,
        private readonly bool $showFileIcons = false,
    ) {
        parent::__construct($name, $plainContent, $content, $value);
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getLevel(): int
    {
        return $this->level;
    }

    public function isShowFileIcons(): bool
    {
        return $this->showFileIcons;
    }

}
