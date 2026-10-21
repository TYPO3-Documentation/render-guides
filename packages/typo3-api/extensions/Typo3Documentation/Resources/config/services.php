<?php

declare(strict_types=1);

use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use T3Docs\Typo3Api\Documentation\Compiler\DocumentationBuilder;
use T3Docs\Typo3Api\Documentation\Index\DocumentationIndex;

return static function (ContainerConfigurator $container): void {
    $container->services()
        ->defaults()
        ->autowire()
        ->autoconfigure()
        ->set(DocumentationIndex::class)
        // phpDocumentor tags only its own services by the interface. Without
        // the tag, "phpdoc settings:list" does not name the setting.
        ->set(DocumentationBuilder::class)
        ->tag('app.with_custom_settings');
};
