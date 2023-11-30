<?php

declare(strict_types=1);

use phpDocumentor\Guides\Cli\Command\Run;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use T3Docs\GuidesExtension\Command\RunDecorator;

use function Symfony\Component\DependencyInjection\Loader\Configurator\service;

return static function (ContainerConfigurator $container): void {
    $container->services()
        ->defaults()
        ->autowire()
        ->set(RunDecorator::class)
        ->decorate(
            Run::class,
        )->args([service('.inner')])
        ->set(\T3Docs\GuidesExtension\Renderer\SinglePageRenderer::class)
        ->tag(
            'phpdoc.renderer.typerenderer',
            [
                'noderender_tag' => 'phpdoc.guides.noderenderer.singlepage',
                'format' => 'singlepage',
            ],
        )
        ->set(\T3Docs\GuidesExtension\Renderer\NodeRenderer\SinglePageDocumentRenderer::class)
        ->tag('phpdoc.guides.noderenderer.singlepage')

        ->set(\phpDocumentor\Guides\NodeRenderers\DelegatingNodeRenderer::class)
        ->call('setNodeRendererFactory', [service('phpdoc.guides.noderenderer.factory.html')])
        ->tag('phpdoc.guides.noderenderer.singlepage')
    ;
};
