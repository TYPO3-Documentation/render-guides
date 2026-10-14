<?php

declare(strict_types=1);

use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use T3Docs\Typo3Api\Json\Twig\ApiJsonExtension;

return static function (ContainerConfigurator $container): void {
    $container->services()
        ->set(ApiJsonExtension::class)
        ->tag('twig.extension');
};
