<?php

declare(strict_types=1);

/**
 * This file is part of phpDocumentor.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @link https://phpdoc.org
 */

namespace T3Docs\Typo3DocsTheme\Compiler\NodeTransformers;

use phpDocumentor\Guides\Compiler\CompilerContextInterface;
use phpDocumentor\Guides\Nodes\AnchorNode;
use phpDocumentor\Guides\Nodes\SectionNode;

use function sprintf;

/**
 * Opt-in heading lint rule (#1157): warns when a section heading has no explicit
 * anchor (a `.. _label:` target), so it cannot be linked to with a stable
 * permalink / `:ref:`.
 *
 * An explicit label is parsed into an {@see AnchorNode}. The upstream
 * MoveAnchorTransformer (priority 30000) runs before this rule (priority 1000)
 * and relocates each such AnchorNode into the section it precedes, so by the
 * time this rule runs a labelled section has the AnchorNode as a direct child.
 * A section is therefore considered anchored when any of its direct children is
 * an AnchorNode. The top-level document title (heading level 1) is exempt: it is
 * addressable by the document path itself.
 */
final class MissingAnchorHeadingLintTransformer extends AbstractHeadingLintTransformer
{
    protected function checkSection(SectionNode $section, CompilerContextInterface $compilerContext): void
    {
        // The document title is referenceable by path; only sub-headings need an explicit anchor.
        if ($section->getTitle()->getLevel() <= 1) {
            return;
        }

        foreach ($section->getChildren() as $child) {
            if ($child instanceof AnchorNode) {
                return;
            }
        }

        $this->logger->warning(
            sprintf('Heading "%s" has no anchor; add a `.. _a-label:` before it so it can be referenced.', $section->getTitle()->toString()),
            $compilerContext->getLoggerInformation(),
        );
    }
}
