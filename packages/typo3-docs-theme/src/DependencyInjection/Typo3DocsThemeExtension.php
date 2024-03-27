<?php

declare(strict_types=1);

namespace T3Docs\Typo3DocsTheme\DependencyInjection;

use phpDocumentor\Guides\NodeRenderers\TemplateNodeRenderer;
use phpDocumentor\Guides\TemplateRenderer;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\DependencyInjection\Loader\PhpFileLoader;
use Symfony\Component\DependencyInjection\Reference;
use T3Docs\Typo3DocsTheme\Nodes\Inline\CodeInlineNode;
use T3Docs\Typo3DocsTheme\Nodes\YoutubeNode;
use T3Docs\Typo3DocsTheme\Settings\Typo3DocsThemeSettings;

use function dirname;
use function phpDocumentor\Guides\DependencyInjection\template;

class Typo3DocsThemeExtension extends Extension implements PrependExtensionInterface
{
    private const HTML = [
        YoutubeNode::class => 'body/directive/youtube.html.twig',
    ];

    /** @param mixed[] $configs */
    public function load(array $configs, ContainerBuilder $container): void
    {
        $loader = new PhpFileLoader(
            $container,
            new FileLocator(dirname(__DIR__, 2) . '/resources/config'),
        );
        $loader->load('typo3-docs-theme.php');
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
            $definition = new Definition(
                Typo3DocsThemeSettings::class,
                [
                    '$settings' => [
                        'typo3_version' => $this->getConfigValue($configs, 'typo3_version', 'main'),
                        'edit_on_github' => $this->getConfigValue($configs, 'edit_on_github', ''),
                        'edit_on_github_branch' => $this->getConfigValue($configs, 'edit_on_github_branch', 'main'),
                        'edit_on_github_directory' => $this->getConfigValue($configs, 'edit_on_github_directory', 'Documentation'),
                        'how_to_edit' => $this->getConfigValue($configs, 'how_to_edit', 'https://docs.typo3.org/m/typo3/docs-how-to-document/main/en-us/WritingDocsOfficial/GithubMethod.html'),
                        'interlink_shortcode' => $this->getConfigValue($configs, 'interlink_shortcode', ''),
                        'copy_sources' => $this->getConfigValue($configs, 'copy_sources', 'true'),
                        'project_home' => $this->getConfigValue($configs, 'project_home', ''),
                        'project_contact' => $this->getConfigValue($configs, 'project_contact', ''),
                        'project_repository' => $this->getConfigValue($configs, 'project_repository', ''),
                        'project_issues' => $this->getConfigValue($configs, 'project_issues', ''),
                        'typo3_core_preferred' => $this->getConfigValue($configs, 'typo3_core_preferred', ''),
                    ],
                ],
            );
            $container->setDefinition(Typo3DocsThemeSettings::class, $definition);
        }
    }

    /**
     * @param array<int, mixed> $configs
     * @return string
     */
    private function getConfigValue(array $configs, string $key, string $default): string
    {
        if (!is_array($configs[1] ?? false) || !isset($configs[1][$key]) || !is_scalar($configs[1][$key])) {
            return $default;
        }
        return strval($configs[1][$key]);
    }

    public function prepend(ContainerBuilder $container): void
    {
        $container->prependExtensionConfig('guides', [
            'themes' => [
                'typo3docs' => [
                    'extends' => 'bootstrap',
                    'templates' => [dirname(__DIR__, 2) . '/resources/template'],
                ],
            ],

            'templates' => [
                template(CodeInlineNode::class, 'inline/textroles/code.html.twig'),
            ],
        ]);
    }
}
