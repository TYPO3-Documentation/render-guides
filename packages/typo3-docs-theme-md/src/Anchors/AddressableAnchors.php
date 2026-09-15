<?php

declare(strict_types=1);

namespace T3Docs\Typo3DocsThemeMd\Anchors;

use phpDocumentor\Guides\Nodes\ProjectNode;
use phpDocumentor\Guides\Nodes\SectionNode;

/**
 * Whether an anchor may be addressed from inside the single Markdown file.
 *
 * The single file holds the whole manual, so a link between two of its pages
 * belongs inside it. That only works where the anchor identifies one place.
 * ProjectNode::addInternalTarget() enforces exactly that for every link type it
 * knows, refusing a second target of the same name with an exception -- with
 * one exception of its own, "std:title", which it waves through because
 * headings repeat. The TYPO3 Core API manual has "Configuration" as a heading
 * often enough to show what that would cost.
 *
 * So: registered under any type but "std:title" means unique, and unique means
 * addressable. Everything else keeps its permalink, which at least lands where
 * it claims to.
 *
 * Asked by both sides -- the renderer that writes the anchors and the Twig
 * function that decides whether a link points inward. They have to agree, or
 * one writes anchors nobody links to while the other writes links to nothing.
 */
final class AddressableAnchors
{
    public static function isAddressable(ProjectNode $projectNode, string $anchor): bool
    {
        if ($anchor === '') {
            return false;
        }

        foreach ($projectNode->getAllInternalTargets() as $linkType => $targets) {
            if ($linkType === SectionNode::STD_TITLE) {
                continue;
            }

            if (isset($targets[$anchor])) {
                return true;
            }
        }

        return false;
    }
}
