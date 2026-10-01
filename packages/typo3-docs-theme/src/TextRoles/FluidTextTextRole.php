<?php

namespace T3Docs\Typo3DocsTheme\TextRoles;

use phpDocumentor\Guides\Nodes\Inline\InlineNode;
use phpDocumentor\Guides\RestructuredText\Parser\DocumentParserContext;
use phpDocumentor\Guides\RestructuredText\TextRoles\TextRole;
use T3Docs\Typo3DocsTheme\Nodes\Inline\CodeInlineNode;

use function preg_match;
use function trim;

final class FluidTextTextRole implements TextRole
{
    /**
     * A ViewHelper at the start of the code, the way a template writes it:
     * as a tag, "<f:format.html>" or "</f:format.html>"; inline,
     * "{f:translate(…)}" or "{f:uri.image}"; or by its name alone,
     * "f:uri.resource".
     */
    private const VIEW_HELPER = '/^(?:<\/?|\{)?([a-zA-Z][a-zA-Z0-9]*:[a-zA-Z][a-zA-Z0-9.]*)(?=[\s>\/(}]|$)/';

    /** The key under which the code carries the ViewHelper it names. */
    public const VIEW_HELPER_INFO = 'viewHelper';

    public function getName(): string
    {
        return 'fluid';
    }

    public function getAliases(): array
    {
        return [];
    }

    public function processNode(DocumentParserContext $documentParserContext, string $role, string $content, string $rawContent): InlineNode
    {
        // What the ViewHelper does is known once every page is parsed, the
        // pages of the ViewHelper Reference included.
        // @see \T3Docs\Typo3DocsTheme\Compiler\NodeTransformers\DescribeFluidViewHelpersTransformer
        $info = preg_match(self::VIEW_HELPER, trim($rawContent), $matches) === 1 ? [self::VIEW_HELPER_INFO => $matches[1]] : [];

        return new CodeInlineNode($rawContent, 'Code written in Fluid', 'Templating engine used by TYPO3.', $info);
    }
}
