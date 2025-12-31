<?php

namespace T3Docs\GuidesExtension\DependencyInjection;

use phpDocumentor\Guides\Renderer\HtmlRenderer;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader\PhpFileLoader;
use T3Docs\GuidesExtension\Renderer\IncrementalTypeRenderer;

final class Typo3GuidesExtension extends Extension implements CompilerPassInterface
{
    public function load(array $configs, ContainerBuilder $container): void
    {
        $loader = new PhpFileLoader(
            $container,
            new FileLocator(dirname(__DIR__, 2) . '/resources/config'),
        );

        $loader->load('typo3-guides.php');
    }

    public function process(ContainerBuilder $container): void
    {
        // Replace HtmlRenderer with IncrementalTypeRenderer for HTML output
        if (!$container->hasDefinition(HtmlRenderer::class) || !$container->hasDefinition(IncrementalTypeRenderer::class)) {
            return;
        }

        $htmlRenderer = $container->getDefinition(HtmlRenderer::class);
        $incrementalRenderer = $container->getDefinition(IncrementalTypeRenderer::class);

        // Copy all tags from HtmlRenderer to IncrementalTypeRenderer
        /** @var array<string, list<array<string, mixed>>> $tags */
        $tags = $htmlRenderer->getTags();
        foreach ($tags as $tagName => $tagAttributesList) {
            foreach ($tagAttributesList as $attributes) {
                $incrementalRenderer->addTag($tagName, $attributes);
            }
        }

        // Replace HtmlRenderer with IncrementalTypeRenderer
        $container->setDefinition(HtmlRenderer::class, $incrementalRenderer);

        // Remove the original IncrementalTypeRenderer definition since we moved it
        $container->removeDefinition(IncrementalTypeRenderer::class);
    }
}
