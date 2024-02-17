<?php

namespace T3Docs\Typo3DocsTheme\EventListeners;

use phpDocumentor\Guides\Event\PostCollectFilesForParsingEvent;
use phpDocumentor\Guides\Files;

final class IgnoreLocalizationsFolders
{
    /**
     * Format as described here: https://docs.typo3.org/m/typo3/docs-how-to-document/main/en-us/HowToAddTranslation/Index.html
     * Currently only simplified tags of form xx_YY are supported.
     * todo: change this to BCP 47 in the future? deployment actions and language/version switch have to be changed accordingly
     * @see https://regex101.com/r/zUNAFQ/1
     */
    private const LOCALIZATION_FOLDER_REGEX = '/^Localization\\.[a-z]{2}_[A-Z]{2}/s';
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
