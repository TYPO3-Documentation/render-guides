<?php

declare(strict_types=1);

namespace MyVendor\MyExtension\Service;

use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;

final readonly class GreetingService
{
    public function __construct(
        private LanguageServiceFactory $languageServiceFactory,
    ) {}

    public function greet(string $name, BackendUserAuthentication $backendUser): string
    {
        $languageService = $this->languageServiceFactory->createFromUserPreferences($backendUser);

        return sprintf(
            $languageService->sL('LLL:EXT:my_extension/Resources/Private/Language/locallang.xlf:greeting'),
            $name,
        );
    }
}
