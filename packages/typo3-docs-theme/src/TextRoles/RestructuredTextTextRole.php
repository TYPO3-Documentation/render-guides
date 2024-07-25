<?php

namespace T3Docs\Typo3DocsTheme\TextRoles;

use phpDocumentor\Guides\Nodes\Inline\InlineNode;
use phpDocumentor\Guides\RestructuredText\Parser\DocumentParserContext;
use phpDocumentor\Guides\RestructuredText\TextRoles\TextRole;
use T3Docs\Typo3DocsTheme\Nodes\Inline\CodeInlineNode;

final class RestructuredTextTextRole implements TextRole
{
    public function getName(): string
    {
        return 'rst';
    }

    public function getAliases(): array
    {
        return ['rest'];
    }

    public function processNode(DocumentParserContext $documentParserContext, string $role, string $content, string $rawContent): InlineNode
    {
        return new CodeInlineNode($rawContent, 'Code written in reStructuredText', 'Easy-to-read, what-you-see-is-what-you-get plaintext markup syntax and parser system.');
    }
}
