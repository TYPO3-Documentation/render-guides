<?php

namespace T3Docs\Typo3DocsTheme\Nodes;

use phpDocumentor\Guides\Nodes\AbstractNode;

/**
 * @extends AbstractNode<string>
 */
final class PageLinkNode extends AbstractNode
{
    public function __construct(
        private readonly string $url,
        private readonly string $title,
        private readonly string $relation
    ) {
        $this->value = $url;
    }

    public function getUrl(): string
    {
        return $this->url;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function getRelation(): string
    {
        return $this->relation;
    }
}
