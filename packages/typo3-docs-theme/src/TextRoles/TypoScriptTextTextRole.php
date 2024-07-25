<?php

namespace T3Docs\Typo3DocsTheme\TextRoles;

use phpDocumentor\Guides\Nodes\Inline\InlineNode;
use phpDocumentor\Guides\RestructuredText\Parser\DocumentParserContext;
use phpDocumentor\Guides\RestructuredText\TextRoles\TextRole;
use T3Docs\Typo3DocsTheme\Nodes\Inline\CodeInlineNode;

final class TypoScriptTextTextRole implements TextRole
{
    public function getName(): string
    {
        return 'typoscript';
    }

    public function getAliases(): array
    {
        return [];
    }

    public function processNode(DocumentParserContext $documentParserContext, string $role, string $content, string $rawContent): InlineNode
    {
        return new CodeInlineNode($rawContent, 'Code written in TypoScript', 'Directive-based configuration language used by TYPO3.');
    }
}
