<?php

namespace T3Docs\Typo3DocsTheme\TextRoles;

use phpDocumentor\Guides\Nodes\Inline\GenericTextRoleInlineNode;
use phpDocumentor\Guides\Nodes\Inline\InlineNode;
use phpDocumentor\Guides\RestructuredText\Parser\DocumentParserContext;
use phpDocumentor\Guides\RestructuredText\TextRoles\TextRole;

final class PhpTextRole implements TextRole
{
    public function getName(): string
    {
        return 'php';
    }

    public function getAliases(): array
    {
        return [];
    }

    public function processNode(DocumentParserContext $documentParserContext, string $role, string $content, string $rawContent): InlineNode
    {
        return new GenericTextRoleInlineNode('literal', $rawContent, 'php');
    }
}
