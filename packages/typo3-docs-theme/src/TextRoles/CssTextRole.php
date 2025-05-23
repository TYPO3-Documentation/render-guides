<?php

namespace T3Docs\Typo3DocsTheme\TextRoles;

use phpDocumentor\Guides\Nodes\Inline\InlineNode;
use phpDocumentor\Guides\RestructuredText\Parser\DocumentParserContext;
use phpDocumentor\Guides\RestructuredText\TextRoles\TextRole;
use T3Docs\Typo3DocsTheme\Nodes\Inline\CodeInlineNode;

final class CssTextRole implements TextRole
{
    public function getName(): string
    {
        return 'css';
    }

    public function getAliases(): array
    {
        return [];
    }

    public function processNode(DocumentParserContext $documentParserContext, string $role, string $content, string $rawContent): InlineNode
    {
        return new CodeInlineNode($rawContent, 'Code written in CSS', 'CSS (Cascading Style Sheets) is used to style the appearance of HTML elements on a web page.');
    }
}
