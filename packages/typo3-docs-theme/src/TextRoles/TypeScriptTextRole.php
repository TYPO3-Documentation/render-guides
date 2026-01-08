<?php

namespace T3Docs\Typo3DocsTheme\TextRoles;

use phpDocumentor\Guides\RestructuredText\Parser\DocumentParserContext;
use phpDocumentor\Guides\RestructuredText\TextRoles\TextRole;
use T3Docs\Typo3DocsTheme\Nodes\Inline\CodeInlineNode;

final class TypeScriptTextRole implements TextRole
{
    #[\Override]
    public function getName(): string
    {
        return 'ts';
    }

    #[\Override]
    public function getAliases(): array
    {
        return ['typescript'];
    }

    #[\Override]
    public function processNode(DocumentParserContext $documentParserContext, string $role, string $content, string $rawContent): \T3Docs\Typo3DocsTheme\Nodes\Inline\CodeInlineNode
    {
        return new CodeInlineNode($rawContent, 'Code written in TypeScript', 'Makes JavaScript utilize type declarations.');
    }
}
