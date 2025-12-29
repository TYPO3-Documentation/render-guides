<?php

namespace T3Docs\Typo3DocsTheme\TextRoles;

use phpDocumentor\Guides\Nodes\Inline\AbstractLinkInlineNode;
use phpDocumentor\Guides\RestructuredText\Parser\DocumentParserContext;
use T3Docs\Typo3DocsTheme\Nodes\Inline\FileInlineNode;

final class FileTextRole extends CustomLinkTextRole
{
    #[\Override]
    public function getName(): string
    {
        return 'file';
    }

    #[\Override]
    public function getAliases(): array
    {
        return [];
    }

    #[\Override]
    protected function createNode(DocumentParserContext $documentParserContext, string $referenceTarget, string|null $referenceName, string $role): AbstractLinkInlineNode
    {
        return $this->createNodeWithInterlink($referenceTarget, '', $referenceName);
    }

    private function createNodeWithInterlink(string $referenceTarget, string $interlinkDomain, string|null $referenceName): \T3Docs\Typo3DocsTheme\Nodes\Inline\FileInlineNode
    {
        return new FileInlineNode($referenceTarget, $referenceName ?? $referenceTarget, $interlinkDomain, 'typo3:file');
    }
}
