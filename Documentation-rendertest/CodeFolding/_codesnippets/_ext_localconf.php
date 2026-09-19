<?php

declare(strict_types=1);

use MyVendor\MyExtension\Controller\BlogController;
use TYPO3\CMS\Extbase\Utility\ExtensionUtility;

defined('TYPO3') or die();

ExtensionUtility::configurePlugin(
    'MyExtension',
    'BlogList',
    [BlogController::class => 'list, show'],
    [BlogController::class => 'delete'],
);
