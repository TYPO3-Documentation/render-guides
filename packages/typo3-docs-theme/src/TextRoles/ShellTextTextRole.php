<?php

namespace T3Docs\Typo3DocsTheme\TextRoles;

use phpDocumentor\Guides\Nodes\Inline\InlineNode;
use phpDocumentor\Guides\RestructuredText\Parser\DocumentParserContext;
use phpDocumentor\Guides\RestructuredText\TextRoles\TextRole;
use T3Docs\Typo3DocsTheme\Nodes\Inline\CodeInlineNode;

final class ShellTextTextRole implements TextRole
{
    public function getName(): string
    {
        return 'shell';
    }

    public function getAliases(): array
    {
        return ['sh', 'bash'];
    }

    public function processNode(DocumentParserContext $documentParserContext, string $role, string $content, string $rawContent): InlineNode
    {
        return new CodeInlineNode($rawContent, 'Shell Script', 'Raw command line interface code on operating-system level.');
    }
}
