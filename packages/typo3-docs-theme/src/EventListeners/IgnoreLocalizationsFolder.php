<?php

namespace T3Docs\Typo3DocsTheme\EventListeners;

use phpDocumentor\Guides\Event\PostCollectFilesForParsingEvent;
use phpDocumentor\Guides\Files;

final class IgnoreLocalizationsFolder
{
    /**
     * @see https://regex101.com/r/zUNAFQ/1
     */
    private const LOCALIZATION_FOLDER_REGEX = '/^Localization\\.[a-z]+_[A-Z]+/s';
    public function __invoke(PostCollectFilesForParsingEvent $event): void
    {
        $files = $event->getFiles();
        $newFiles = new Files();
        foreach ($files as $filePath) {
            if (!preg_match(self::LOCALIZATION_FOLDER_REGEX, $filePath)) {
                $newFiles->add($filePath);
            }
        }
        $event->setFiles($newFiles);
    }
}
