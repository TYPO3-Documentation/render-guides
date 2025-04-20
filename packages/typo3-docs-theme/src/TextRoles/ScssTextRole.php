<?php

namespace T3Docs\Typo3DocsTheme\TextRoles;

use phpDocumentor\Guides\Nodes\Inline\InlineNode;
use phpDocumentor\Guides\RestructuredText\Parser\DocumentParserContext;
use phpDocumentor\Guides\RestructuredText\TextRoles\TextRole;
use T3Docs\Typo3DocsTheme\Nodes\Inline\CodeInlineNode;

final class ScssTextRole implements TextRole
{
    public function getName(): string
    {
        return 'scss';
    }

    public function getAliases(): array
    {
        return ['sass'];
    }

    public function processNode(DocumentParserContext $documentParserContext, string $role, string $content, string $rawContent): InlineNode
    {
        return new CodeInlineNode($rawContent, 'Code written in SCSS', 'SCSS is a syntax of Sass, a CSS preprocessor that adds variables, nesting, and functions to make writing styles more powerful and maintainable.');
    }
}
