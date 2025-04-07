<?php

namespace T3Docs\Typo3DocsTheme\Nodes;

use phpDocumentor\Guides\Nodes\Inline\PlainTextInlineNode;
use phpDocumentor\Guides\Nodes\InlineCompoundNode;
use phpDocumentor\Guides\Nodes\SectionNode;
use phpDocumentor\Guides\RestructuredText\Nodes\GeneralDirectiveNode;

final class Typo3TalkNode extends GeneralDirectiveNode
{
    private ?SectionNode $sectionNode = null;
    public function __construct(
    ) {

        parent::__construct('typo3:talk', '', new InlineCompoundNode([new PlainTextInlineNode('')]));
    }

    public function getSectionNode(): ?SectionNode
    {
        return $this->sectionNode;
    }

    public function setSectionNode(?SectionNode $sectionNode): void
    {
        $this->sectionNode = $sectionNode;
    }
}
