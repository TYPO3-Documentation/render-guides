<?php

declare(strict_types=1);

use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use T3Docs\Typo3DocsThemeMd\Renderer\MdRenderer;
use T3Docs\Typo3DocsThemeMd\Twig\MdExtension;

use function Symfony\Component\DependencyInjection\Loader\Configurator\service;

return static function (ContainerConfigurator $container): void {
    $container->services()
        ->defaults()
        ->autowire()
        ->autoconfigure()

        ->load(
            'T3Docs\\Typo3DocsThemeMd\\NodeRenderers\\Md\\',
            '../../src/NodeRenderers/Md',
        )
        ->tag('phpdoc.guides.noderenderer.md')

        ->set(MdRenderer::class)
        ->tag(
            'phpdoc.renderer.typerenderer',
            [
                'noderender_tag' => 'phpdoc.guides.noderenderer.md',
                'format' => 'md',
            ],
        )


        ->set(MdExtension::class)
        ->arg('$nodeRenderer', service('phpdoc.guides.output_node_renderer'))
        ->tag('twig.extension')
        ->autowire();
};
