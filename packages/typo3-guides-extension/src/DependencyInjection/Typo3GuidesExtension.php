<?php

namespace T3Docs\GuidesExtension\DependencyInjection;

use phpDocumentor\Guides\Renderer\HtmlRenderer;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader\PhpFileLoader;
use T3Docs\GuidesExtension\Renderer\Parallel\ForkingRenderer;

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
        // NOTE: Parallel parsing is available (ParallelParseDirectoryHandler) but NOT enabled
        // by default. Testing shows the overhead of forking and serializing DocumentNodes
        // outweighs gains for typical document sizes. The serialization cost through temp
        // files makes it slower than sequential parsing for most use cases.

        // Replace HtmlRenderer with ForkingRenderer for HTML output.
        // This enables parallel rendering using pcntl_fork for better performance.
        // ForkingRenderer uses DocumentNavigationProvider to maintain correct
        // prev/next navigation links across all forked child processes.
        $this->replaceHtmlRenderer($container);
    }

    private function replaceHtmlRenderer(ContainerBuilder $container): void
    {
        if (!$container->hasDefinition(HtmlRenderer::class) || !$container->hasDefinition(ForkingRenderer::class)) {
            return;
        }

        $htmlRenderer = $container->getDefinition(HtmlRenderer::class);
        $forkingRenderer = $container->getDefinition(ForkingRenderer::class);

        // Copy all tags from HtmlRenderer to ForkingRenderer
        /** @var array<string, list<array<string, mixed>>> $tags */
        $tags = $htmlRenderer->getTags();
        foreach ($tags as $tagName => $tagAttributesList) {
            foreach ($tagAttributesList as $attributes) {
                $forkingRenderer->addTag($tagName, $attributes);
            }
        }

        // Replace HtmlRenderer with ForkingRenderer
        $container->setDefinition(HtmlRenderer::class, $forkingRenderer);
        $container->removeDefinition(ForkingRenderer::class);
    }
}
