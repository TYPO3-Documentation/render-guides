<?php

declare(strict_types=1);

use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use T3Docs\Typo3Api\Events\Compiler\DispatchedByBuilder;
use T3Docs\Typo3Api\Events\Reflection\DispatchedEventsReducer;

return static function (ContainerConfigurator $container): void {
    $container->services()
        ->defaults()
        ->autowire()
        ->autoconfigure()
        ->set(DispatchedEventsReducer::class)
        ->tag('phpdoc.reflection.reducer')
        ->set(DispatchedByBuilder::class);
};
