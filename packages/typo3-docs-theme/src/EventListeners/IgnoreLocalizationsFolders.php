<?php

namespace T3Docs\Typo3DocsTheme\EventListeners;

use phpDocumentor\Guides\Event\PostCollectFilesForParsingEvent;
use phpDocumentor\Guides\Files;
use T3Docs\Typo3DocsTheme\Settings\Typo3DocsInputSettings;

final class IgnoreLocalizationsFolders
{
    /**
     * Format as described here: https://docs.typo3.org/m/typo3/docs-how-to-document/main/en-us/HowToAddTranslation/Index.html
     * Currently only simplified tags of form xx_YY are supported.
     * todo: change this to BCP 47 in the future? deployment actions and language/version switch have to be changed accordingly
     * @see https://regex101.com/r/zUNAFQ/1
     */
    private const LOCALIZATION_FOLDER_REGEX = '/^Localization\\.[a-z]{2}_[A-Z]{2}/s';

    public function __construct(private readonly Typo3DocsInputSettings $input) {}

    public function __invoke(PostCollectFilesForParsingEvent $event): void
    {
        $files = $event->getFiles();
        $newFiles = new Files();
        // In case render-guides uses the '--localization' parameter, no exclusion of localizations is wanted.
        // Only when the base language is checked, no localizations shall be evaluated.
        if ($this->input->getInput()?->hasParameterOption('--localization')) {
            return;
        }

        foreach ($files as $filePath) {
            if (!preg_match(self::LOCALIZATION_FOLDER_REGEX, $filePath)) {
                $newFiles->add($filePath);
            }
        }
        $event->setFiles($newFiles);
    }
}
