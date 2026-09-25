<?php

declare(strict_types=1);

namespace MyVendor\MyExtension\Service;

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;

class GreetingService
{
    public function greet(string $name): string
    {
        $languageServiceFactory = GeneralUtility::makeInstance(LanguageServiceFactory::class);
        $languageService = $languageServiceFactory->createFromUserPreferences($GLOBALS['BE_USER']);

        return sprintf(
            $languageService->sL('LLL:EXT:my_extension/Resources/Private/Language/locallang.xlf:greeting'),
            $name,
        );
    }
}
