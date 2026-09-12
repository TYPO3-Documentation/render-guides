<?php

declare(strict_types=1);

namespace T3Docs\Typo3DocsThemeMd\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\DependencyInjection\Loader\PhpFileLoader;
use T3Docs\Typo3DocsThemeMd\Renderer\UnsupportedNodeRenderer;

use function dirname;
use function phpDocumentor\Guides\DependencyInjection\templateArray;

/**
 * Registers the "md" output format: a theme directory of *.md.twig templates
 * and the node-to-template mapping for it, alongside the MdRenderer.
 *
 * Same shape as phpdocumentor/guides-theme-rst, which is the only other
 * non-HTML render target in the pipeline.
 */
final class Typo3DocsThemeMdExtension extends Extension implements PrependExtensionInterface
{
    /** @param mixed[] $configs */
    public function load(array $configs, ContainerBuilder $container): void
    {
        $loader = new PhpFileLoader(
            $container,
            new FileLocator(dirname(__DIR__, 2) . '/resources/config'),
        );
        $loader->load('typo3-docs-theme-md.php');
    }

    /**
     * Replace the Markdown format's "nothing else matched" renderer with one
     * that leaves a marker behind.
     *
     * That renderer is reachable twice -- through the tagged iterator and as
     * the factory's fallback argument -- so decorating it would either be
     * bypassed or, if tagged, take precedence over the templates. Swapping the
     * definition in place keeps both paths pointing at the same object, and the
     * original is kept as the inner renderer so its output is preserved.
     *
     * Must run after the guides RendererPass, which creates the service.
     */
    public function markUnsupportedNodes(ContainerBuilder $container): void
    {
        $id = 'phpdoc.guides.noderenderer.default.md';
        if (!$container->hasDefinition($id)) {
            return;
        }

        $original = $container->getDefinition($id);
        if ($original->getClass() === UnsupportedNodeRenderer::class) {
            return;
        }

        $innerId = $id . '.inner';
        $inner = clone $original;
        $inner->clearTags();
        $container->setDefinition($innerId, $inner);

        $replacement = new Definition(UnsupportedNodeRenderer::class, ['$inner' => new Reference($innerId)]);
        foreach ($original->getTags() as $tag => $attributes) {
            foreach ($attributes as $attribute) {
                $replacement->addTag($tag, $attribute);
            }
        }

        $container->setDefinition($id, $replacement);
    }

    public function prepend(ContainerBuilder $container): void
    {
        // A base template path, not a theme: the active theme is whatever the
        // HTML output uses (typo3docs), and ThemeManager searches the base
        // paths under every theme. Registering "md" as a theme instead would
        // force a project to choose between HTML and Markdown output.
        $container->prependExtensionConfig('guides', [
            'base_template_paths' => [dirname(__DIR__, 2) . '/resources/template/md'],
        ]);

        $container->prependExtensionConfig(
            'guides',
            [
                'templates' => templateArray(
                    require dirname(__DIR__, 2) . '/resources/template/md/template.php',
                    'md',
                ),
            ],
        );
    }
}
