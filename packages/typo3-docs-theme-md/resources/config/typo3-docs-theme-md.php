<?php

declare(strict_types=1);

use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use T3Docs\Typo3DocsThemeMd\Renderer\MdRenderer;
use T3Docs\Typo3DocsThemeMd\Renderer\NodeRenderer\AnchoringNodeRenderer;
use T3Docs\Typo3DocsThemeMd\Renderer\NodeRenderer\SingleMarkdownDocumentRenderer;
use T3Docs\Typo3DocsThemeMd\Renderer\SingleMarkdownRenderer;
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

        // The single-file variant. Its own node renderer tag, because only the
        // DocumentNode is rendered differently -- inline instead of as a file
        // of its own -- and everything below it reuses the "md" renderers
        // through the delegating renderer below.
        ->set(SingleMarkdownRenderer::class)
        ->tag(
            'phpdoc.renderer.typerenderer',
            [
                'noderender_tag' => 'phpdoc.guides.noderenderer.singlemd',
                'format' => 'singlemd',
            ],
        )

        ->set(SingleMarkdownDocumentRenderer::class)
        ->tag('phpdoc.guides.noderenderer.singlemd')

        // The catch-all for the single file: delegates to the "md" renderers
        // and writes the anchor of whatever it rendered in front of it.
        ->set(AnchoringNodeRenderer::class)
        ->call('setNodeRendererFactory', [service('phpdoc.guides.noderenderer.factory.md')])
        ->tag('phpdoc.guides.noderenderer.singlemd')

        ->set(MdExtension::class)
        ->arg('$nodeRenderer', service('phpdoc.guides.output_node_renderer'))
        ->tag('twig.extension')
        ->autowire();
};
