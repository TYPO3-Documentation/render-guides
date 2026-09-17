<?php

declare(strict_types=1);

namespace T3Docs\Typo3DocsTheme\EventListeners;

use phpDocumentor\Guides\Event\ModifyDocumentEntryAdditionalData;
use phpDocumentor\Guides\Nodes\TitleNode;

/**
 * Give a page's ":navigation-title:" the anchor of the page it names.
 *
 * A menu entry is rendered as a link to the id of the title it carries, and for
 * a page with a ":navigation-title:" that title is built with
 * "TitleNode::fromString()", which has no id to give -- it is made from a bare
 * string, not from a parsed heading. The menu entry then links to "#".
 *
 * In the per-page HTML that goes unnoticed: the link carries a file name as
 * well, so it opens the page and merely misses the jump. In the single-page
 * HTML there is no file name, and the link is nothing but the anchor: the
 * ViewHelper reference publishes 1079 of them, every one reading href="#".
 *
 * The id to use is the one the page's own heading has, because that is what the
 * section is written with in both renderings. The navigation title only changes
 * what the entry reads, never where it leads.
 */
final class NavigationTitleAnchorSetter
{
    public function __invoke(ModifyDocumentEntryAdditionalData $event): void
    {
        $additionalData = $event->getAdditionalData();
        $navigationTitle = $additionalData['navigationTitle'] ?? null;
        if (!$navigationTitle instanceof TitleNode || $navigationTitle->getId() !== '') {
            return;
        }

        $id = $event->getDocumentNode()->getTitle()?->getId() ?? '';
        if ($id === '') {
            return;
        }

        $navigationTitle->setId($id);
        $additionalData['navigationTitle'] = $navigationTitle;
        $event->setAdditionalData($additionalData);
    }
}
