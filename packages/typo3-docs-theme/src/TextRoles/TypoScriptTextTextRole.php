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

final class TypoScriptTextTextRole implements TextRole
{
    /** An object type, such as "PAGEVIEW", "FLUIDTEMPLATE" or "USER_INT". */
    private const OBJECT_TYPE = '/^[A-Z][A-Z0-9_]*$/';

    /** A word of its own, such as the function "stdWrap", "HTMLparser" or "HTMLparser_tags". */
    private const WORD = '/^[A-Za-z][A-Za-z0-9_]*$/';

    /** The key under which the code carries the option path it names. */
    public const OPTION_INFO = 'typoscriptOption';

    /** The key under which the code carries the object type it names. */
    public const OBJECT_INFO = 'typoscriptObject';

    /** The key under which the code carries a word of its own. */
    public const WORD_INFO = 'typoscriptWord';

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
        // What the option or object is, is known once every page is parsed,
        // the pages of the TypoScript reference included.
        // @see \T3Docs\Typo3DocsTheme\Compiler\NodeTransformers\DescribeTypoScriptTransformer
        [$code, $confval] = NamedConfval::split(trim($rawContent));
        if ($confval !== '') {
            return new CodeInlineNode($code, 'Code written in TypoScript', 'Directive-based configuration language used by TYPO3.', [NamedConfval::INFO => $confval, NamedConfval::ROLE_INFO => 'typoscript']);
        }

        $info = match (true) {
            preg_match(OptionPaths::PATTERN, $code) === 1 => [self::OPTION_INFO => $code],
            preg_match(self::OBJECT_TYPE, $code) === 1 => [self::OBJECT_INFO => $code],
            preg_match(self::WORD, $code) === 1 => [self::WORD_INFO => $code],
            default => [],
        };

        return new CodeInlineNode($rawContent, 'Code written in TypoScript', 'Directive-based configuration language used by TYPO3.', $info);
    }
}
