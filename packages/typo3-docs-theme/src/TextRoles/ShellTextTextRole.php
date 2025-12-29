<?php

namespace T3Docs\Typo3DocsTheme\TextRoles;

use phpDocumentor\Guides\RestructuredText\Parser\DocumentParserContext;
use phpDocumentor\Guides\RestructuredText\TextRoles\TextRole;
use T3Docs\Typo3DocsTheme\Nodes\Inline\CodeInlineNode;

final class ShellTextTextRole implements TextRole
{
    #[\Override]
    public function getName(): string
    {
        return 'shell';
    }

    #[\Override]
    public function getAliases(): array
    {
        return ['sh', 'bash'];
    }

    #[\Override]
    public function processNode(DocumentParserContext $documentParserContext, string $role, string $content, string $rawContent): \T3Docs\Typo3DocsTheme\Nodes\Inline\CodeInlineNode
    {
        return new CodeInlineNode($rawContent, 'Code written in Shell Script', 'Raw command line interface code on operating-system level.');
    }
}
