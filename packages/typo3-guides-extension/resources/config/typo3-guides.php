<?php

declare(strict_types=1);

use phpDocumentor\Guides\Cli\Command\Run;
use phpDocumentor\Guides\Interlink\InventoryRepository;
use phpDocumentor\Guides\Renderer\UrlGenerator\UrlGeneratorInterface;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use T3Docs\GuidesExtension\Command\RunDecorator;
use T3Docs\GuidesExtension\Compiler\Cache\CacheVersioning;
use T3Docs\GuidesExtension\Compiler\Cache\ChangeDetector;
use T3Docs\GuidesExtension\Compiler\Cache\ContentHasher;
use T3Docs\GuidesExtension\Compiler\Cache\DirtyPropagator;
use T3Docs\GuidesExtension\Compiler\Cache\GlobalInvalidationDetector;
use T3Docs\GuidesExtension\Compiler\Cache\IncrementalBuildCache;
use T3Docs\GuidesExtension\Compiler\Passes\DependencyGraphPass;
use T3Docs\GuidesExtension\Compiler\Passes\ExportsCollectorPass;
use T3Docs\GuidesExtension\Renderer\UrlGenerator\RenderOutputUrlGenerator;
use T3Docs\GuidesExtension\Renderer\UrlGenerator\SingleHtmlUrlGenerator;
use T3Docs\GuidesExtension\EventListener\IncrementalCacheListener;
use T3Docs\GuidesExtension\Renderer\IncrementalTypeRenderer;
use T3Docs\Typo3DocsTheme\Inventory\Typo3InventoryRepository;

use function Symfony\Component\DependencyInjection\Loader\Configurator\param;
use function Symfony\Component\DependencyInjection\Loader\Configurator\service;

return static function (ContainerConfigurator $container): void {
    $container->services()
        ->defaults()
        ->autowire()

        // Original TYPO3 Guides Extension services
        ->set(Run::class, RunDecorator::class)
        ->public()
        ->tag('phpdoc.guides.cli.command')
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

        ->set(InventoryRepository::class, Typo3InventoryRepository::class)
        ->arg('$inventoryConfigs', param('phpdoc.guides.inventories'))

        ->set(SingleHtmlUrlGenerator::class)
        ->set(UrlGeneratorInterface::class, RenderOutputUrlGenerator::class)

        ->set(\phpDocumentor\Guides\NodeRenderers\DelegatingNodeRenderer::class)
        ->call('setNodeRendererFactory', [service('phpdoc.guides.noderenderer.factory.html')])
        ->tag('phpdoc.guides.noderenderer.singlepage')

        // Incremental Rendering - Cache infrastructure
        ->set(ContentHasher::class)
        ->set(CacheVersioning::class)
        ->set(IncrementalBuildCache::class)
            ->arg('$versioning', service(CacheVersioning::class))
        ->set(ChangeDetector::class)
            ->arg('$hasher', service(ContentHasher::class))
        ->set(DirtyPropagator::class)
        ->set(GlobalInvalidationDetector::class)

        // Incremental Rendering - Compiler passes
        ->set(ExportsCollectorPass::class)
            ->arg('$cache', service(IncrementalBuildCache::class))
            ->arg('$hasher', service(ContentHasher::class))
            ->tag('phpdoc.guides.compiler.passes')
        ->set(DependencyGraphPass::class)
            ->arg('$cache', service(IncrementalBuildCache::class))
            ->tag('phpdoc.guides.compiler.passes')

        // Incremental Rendering - Event Listeners
        ->set(IncrementalCacheListener::class)
            ->arg('$cache', service(IncrementalBuildCache::class))
            ->arg('$changeDetector', service(ChangeDetector::class))
            ->arg('$hasher', service(ContentHasher::class))
            ->arg('$invalidationDetector', service(GlobalInvalidationDetector::class))
            ->tag('event_listener', ['event' => 'phpDocumentor\Guides\Event\PostProjectNodeCreated', 'method' => 'onPostProjectNodeCreated'])
            ->tag('event_listener', ['event' => 'phpDocumentor\Guides\Event\PostCollectFilesForParsingEvent', 'method' => 'onPostCollectFilesForParsing'])
            ->tag('event_listener', ['event' => 'phpDocumentor\Guides\Event\PostRenderProcess', 'method' => 'onPostRenderProcess'])

        // Incremental Rendering - Type Renderer (replaces HtmlRenderer via compiler pass)
        ->set(IncrementalTypeRenderer::class)
            ->arg('$commandBus', service('League\Tactician\CommandBus'))
            ->arg('$cache', service(IncrementalBuildCache::class))
            ->arg('$cacheListener', service(IncrementalCacheListener::class))
            ->arg('$logger', service('Psr\Log\LoggerInterface')->nullOnInvalid())
    ;
};
