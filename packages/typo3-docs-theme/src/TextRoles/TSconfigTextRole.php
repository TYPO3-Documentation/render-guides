<?php

namespace T3Docs\Typo3DocsTheme\TextRoles;

use phpDocumentor\Guides\Nodes\Inline\InlineNode;
use phpDocumentor\Guides\RestructuredText\Parser\DocumentParserContext;
use phpDocumentor\Guides\RestructuredText\TextRoles\TextRole;
use T3Docs\Typo3DocsTheme\Nodes\Inline\CodeInlineNode;
use T3Docs\Typo3DocsTheme\TypoScriptReference\NamedConfval;
use T3Docs\Typo3DocsTheme\TypoScriptReference\OptionPaths;

use function preg_match;
use function trim;

final class TSconfigTextRole implements TextRole
{
    /** The key under which the code carries the option path it names. */
    public const OPTION_INFO = 'tsconfigOption';

    public function getName(): string
    {
        return 'tsconfig';
    }

    public function getAliases(): array
    {
        return [];
    }

    public function processNode(DocumentParserContext $documentParserContext, string $role, string $content, string $rawContent): InlineNode
    {
        // What the option is, is known once every page is parsed, the pages
        // of the TypoScript reference included, which documents TSconfig too.
        // @see \T3Docs\Typo3DocsTheme\Compiler\NodeTransformers\DescribeTypoScriptTransformer
        [$code, $confval] = NamedConfval::split(trim($rawContent));
        if ($confval !== '') {
            return new CodeInlineNode($code, 'Code written in TSconfig', 'TypoScript Configuration directives.', [NamedConfval::INFO => $confval, NamedConfval::ROLE_INFO => 'tsconfig']);
        }

        $info = preg_match(OptionPaths::PATTERN, $code) === 1 ? [self::OPTION_INFO => $code] : [];

        return new CodeInlineNode($rawContent, 'Code written in TSconfig', 'TypoScript Configuration directives.', $info);
    }
}
