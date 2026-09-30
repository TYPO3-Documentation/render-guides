<?php

declare(strict_types=1);

namespace T3Docs\Typo3DocsTheme\Nodes\Inline;

/**
 * An inline node that renders plainer inside a headline: as a literal in
 * backticks, without the infobox or popup it opens elsewhere.
 *
 * @see \T3Docs\Typo3DocsTheme\Compiler\NodeTransformers\CodeInHeadlineNodeTransformer
 */
interface InHeadline
{
    public function markInHeadline(): void;

    public function isInHeadline(): bool;
}
