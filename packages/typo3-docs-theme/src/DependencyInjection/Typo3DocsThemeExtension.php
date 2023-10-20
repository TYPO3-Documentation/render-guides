<?php

declare(strict_types=1);

namespace T3Docs\Typo3DocsTheme\DependencyInjection;

use phpDocumentor\Guides\NodeRenderers\TemplateNodeRenderer;
use phpDocumentor\Guides\RestructuredText\Nodes\VersionChangeNode;
use phpDocumentor\Guides\TemplateRenderer;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;

use Symfony\Component\DependencyInjection\Loader\PhpFileLoader;
use Symfony\Component\DependencyInjection\Reference;
use T3Docs\Typo3DocsTheme\Nodes\ConfvalNode;
use function dirname;

class Typo3DocsThemeExtension extends Extension implements PrependExtensionInterface
{
    private const HTML = [ConfvalNode::class => 'custom/confval.html.twig'];

    /** @param mixed[] $configs */
    public function load(array $configs, ContainerBuilder $container): void
    {
        foreach (self::HTML as $node => $template) {
            $definition = new Definition(
                TemplateNodeRenderer::class,
                [
                    '$renderer' => new Reference(TemplateRenderer::class),
                    '$template' => $template,
                    '$nodeClass' => $node,
                ],
            );
            $definition->addTag('phpdoc.guides.noderenderer.html');

            $container->setDefinition('phpdoc.guides.rst.' . substr(strrchr($node, '\\') ?: '', 1), $definition);
        }
    }

    public function prepend(ContainerBuilder $container): void
    {
        $loader = new PhpFileLoader(
            $container,
            new FileLocator(dirname(__DIR__, 2) . '/resources/config'),
        );
        $container->prependExtensionConfig('guides', [
            'themes' => [
                'typo3docs' => [
                    'extends' => 'bootstrap',
                    'templates' => [dirname(__DIR__, 2) . '/resources/template'],
                ],
            ],
        ]);
        $loader->load('typo3-docs-theme.php');
    }
}
