<?php

namespace T3Docs\Typo3DocsTheme\TextRoles;

use phpDocumentor\Guides\RestructuredText\Parser\DocumentParserContext;
use phpDocumentor\Guides\RestructuredText\TextRoles\TextRole;
use T3Docs\Typo3DocsTheme\Nodes\Inline\CodeInlineNode;

final class JavaScriptTextRole implements TextRole
{
    public function getName(): string
    {
        return 'js';
    }

    public function getAliases(): array
    {
        return ['javascript'];
    }

    public function processNode(DocumentParserContext $documentParserContext, string $role, string $content, string $rawContent): \T3Docs\Typo3DocsTheme\Nodes\Inline\CodeInlineNode
    {
        return new CodeInlineNode($rawContent, 'Code written in JavaScript', 'Dynamic client-side scripting language for dynamic applications.');
    }
}
