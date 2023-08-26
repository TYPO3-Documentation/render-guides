<?php

declare(strict_types=1);

use phpDocumentor\Guides\RestructuredText\Parser\Productions\DirectiveContentRule;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use T3Docs\Typo3DocsTheme\Directives\ConfvalDirective;

use function Symfony\Component\DependencyInjection\Loader\Configurator\service;

return static function (ContainerConfigurator $container): void {
    $container->services()
        ->defaults()
        ->autowire()
        ->autoconfigure()
        ->set(ConfvalDirective::class)
        ->bind('$startingRule', service(DirectiveContentRule::class))
        ->tag('phpdoc.guides.directive');
};
