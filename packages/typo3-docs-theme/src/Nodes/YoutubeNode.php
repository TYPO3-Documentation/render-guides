<?php

namespace T3Docs\Typo3DocsTheme\Nodes;

use phpDocumentor\Guides\Nodes\AbstractNode;

/**
 * @extends AbstractNode<string>
 */
final class YoutubeNode extends AbstractNode
{
    public function __construct(
        protected readonly string $youtubeId,
    ) {
        $this->value = $youtubeId;
    }

    public function getYoutubeId(): string
    {
        return $this->youtubeId;
    }
}
