<?php

namespace T3Docs\Typo3DocsTheme\TextRoles;

use phpDocumentor\Guides\RestructuredText\Parser\DocumentParserContext;
use phpDocumentor\Guides\RestructuredText\TextRoles\TextRole;
use T3Docs\Typo3DocsTheme\Nodes\Inline\CodeInlineNode;

final class FluidTextTextRole implements TextRole
{
    public function getName(): string
    {
        return 'fluid';
    }

    public function getAliases(): array
    {
        return [];
    }

    public function processNode(DocumentParserContext $documentParserContext, string $role, string $content, string $rawContent): \T3Docs\Typo3DocsTheme\Nodes\Inline\CodeInlineNode
    {
        return new CodeInlineNode($rawContent, 'Code written in Fluid', 'Templating engine used by TYPO3.');
    }
}
