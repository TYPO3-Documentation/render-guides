<?php

declare(strict_types=1);

namespace T3Docs\Typo3DocsTheme\EventListeners;

use phpDocumentor\Guides\Event\PostParseDocument;

final class OriginalFileNameSetter
{
    public function __invoke(PostParseDocument $event): void
    {
        $event->setDocumentNode(
            $event->getDocumentNode()?->withKeepExistingOptions(['originalFileName' => $event->getOriginalFileName()])
        );
    }
}
