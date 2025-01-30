<?php

namespace T3Docs\Typo3DocsTheme\TextRoles;

use phpDocumentor\Guides\Nodes\Inline\AbstractLinkInlineNode;
use phpDocumentor\Guides\RestructuredText\Parser\DocumentParserContext;
use T3Docs\Typo3DocsTheme\Nodes\Inline\FileInlineNode;

final class FileTextRole extends CustomLinkTextRole
{
    public function getName(): string
    {
        return 'file';
    }

    public function getAliases(): array
    {
        return [];
    }

    protected function createNode(DocumentParserContext $documentParserContext, string $referenceTarget, string|null $referenceName, string $role): AbstractLinkInlineNode
    {
        return $this->createNodeWithInterlink($documentParserContext, $referenceTarget, '', $referenceName);
    }

    private function createNodeWithInterlink(DocumentParserContext $documentParserContext, string $referenceTarget, string $interlinkDomain, string|null $referenceName): AbstractLinkInlineNode
    {
        return new FileInlineNode($referenceTarget, $referenceName ?? $referenceTarget, $interlinkDomain, 'typo3:file');
    }
}
