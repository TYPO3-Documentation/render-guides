<?php

declare(strict_types=1);

use phpDocumentor\Guides\Cli\Command\Run;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

use function Symfony\Component\DependencyInjection\Loader\Configurator\service;

use T3Docs\GuidesExtension\Command\RunDecorator;

return static function (ContainerConfigurator $container): void {
    $container->services()
        ->defaults()
        ->autowire()
        ->set(RunDecorator::class)
        ->decorate(
            Run::class,
        )->args([service('.inner')])
        ->set(\T3Docs\GuidesExtension\Command\ConfigureCommand::class)
        ->tag('phpdoc.guides.cli.command');
};
