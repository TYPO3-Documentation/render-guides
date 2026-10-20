<?php

declare(strict_types=1);

namespace T3Docs\Typo3DocsTheme\ReferenceResolvers;

use phpDocumentor\Guides\Nodes\Inline\LinkInlineNode;
use phpDocumentor\Guides\Nodes\Inline\ReferenceNode;
use phpDocumentor\Guides\ReferenceResolvers\InternalReferenceResolver;
use phpDocumentor\Guides\ReferenceResolvers\Messages;
use phpDocumentor\Guides\ReferenceResolvers\ReferenceResolver;
use phpDocumentor\Guides\RenderContext;

use function str_starts_with;

/**
 * Resolves a link by a named link of its document, as guides' resolver does,
 * but never a reference by a named link to a permalink.
 *
 * A link such as "`ModifyRenderedRecordEvent <https://docs.typo3.org/permalink/
 * t3coreapi:ModifyRenderedRecordEvent>`_" also names the link by its text.
 * @see ReplacePermalinksNodeTransformer turns the permalink into a reference
 * to the anchor, and when the text is the name of the anchor, guides' resolver
 * found that named link first: the reference resolved to the permalink it
 * came from. The link never went to the page in the render, and a missing
 * anchor gave no warning.
 *
 * A reference to a permalink is to an anchor, so it goes on to the anchor
 * resolver. A link to the named link, such as "`ModifyRenderedRecordEvent`_",
 * is turned into that reference as well.
 */
final class NamedLinkReferenceResolver implements ReferenceResolver
{
    private const PERMALINK_PREFIX = 'https://docs.typo3.org/permalink/';

    public function resolve(LinkInlineNode $node, RenderContext $renderContext, Messages $messages): bool
    {
        $link = $renderContext->getLink($node->getTargetReference());
        if ($link === '' || ($node instanceof ReferenceNode && str_starts_with($link, self::PERMALINK_PREFIX))) {
            return false;
        }

        $node->setUrl($link);

        return true;
    }

    public static function getPriority(): int
    {
        return InternalReferenceResolver::PRIORITY;
    }
}
