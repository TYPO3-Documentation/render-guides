<?php

declare(strict_types=1);

namespace T3Docs\Typo3DocsTheme\DependencyInjection;

use phpDocumentor\Guides\Graphs\Nodes\UmlNode;
use phpDocumentor\Guides\NodeRenderers\TemplateNodeRenderer;
use phpDocumentor\Guides\RestructuredText\Directives\FigureDirective as BaseFigureDirective;
use phpDocumentor\Guides\RestructuredText\Directives\IndexDirective as BaseIndexDirective;
use phpDocumentor\Guides\TemplateRenderer;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\DependencyInjection\Loader\PhpFileLoader;
use Symfony\Component\DependencyInjection\Reference;
use phpDocumentor\Guides\Settings\ProjectSettings;
use phpDocumentor\Guides\Settings\SettingsManager;
use T3Docs\GuidesPhpDomain\Nodes\MemberNameNode;
use T3Docs\GuidesPhpDomain\Nodes\PhpCaseNode;
use T3Docs\GuidesPhpDomain\Nodes\PhpComponentNode;
use T3Docs\GuidesPhpDomain\Nodes\PhpConstNode;
use T3Docs\GuidesPhpDomain\Nodes\PhpGlobalNode;
use T3Docs\GuidesPhpDomain\Nodes\PhpMethodNode;
use T3Docs\GuidesPhpDomain\Nodes\PhpModifierNode;
use T3Docs\GuidesPhpDomain\Nodes\PhpNamespaceNode;
use T3Docs\GuidesPhpDomain\Nodes\PhpPropertyNode;
use T3Docs\Typo3DocsTheme\Directives\FigureDirective;
use T3Docs\Typo3DocsTheme\Nodes\Inline\CodeInlineNode;
use T3Docs\Typo3DocsTheme\Nodes\Inline\ComposerInlineNode;
use T3Docs\Typo3DocsTheme\Nodes\Inline\FileInlineNode;
use T3Docs\Typo3DocsTheme\Nodes\Typo3VersionChangeNode;
use T3Docs\Typo3DocsTheme\Nodes\YoutubeNode;
use T3Docs\Typo3DocsTheme\Settings\Typo3DocsInputSettings;
use T3Docs\Typo3DocsThemeMd\DependencyInjection\Typo3DocsThemeMdExtension;
use T3Docs\Typo3DocsTheme\Settings\Typo3DocsThemeSettings;

use function dirname;
use function in_array;
use function strtolower;
use function trim;
use function phpDocumentor\Guides\DependencyInjection\template;

class Typo3DocsThemeExtension extends Extension implements PrependExtensionInterface, CompilerPassInterface
{
    private const HTML = [
        YoutubeNode::class => 'body/directive/youtube.html.twig',
        Typo3VersionChangeNode::class => 'body/version-change.html.twig',
    ];

    /** @param array<int, mixed> $configs */
    public function load(array $configs, ContainerBuilder $container): void
    {
        $loader = new PhpFileLoader(
            $container,
            new FileLocator(dirname(__DIR__, 2) . '/resources/config'),
        );
        $loader->load('typo3-docs-theme.php');

        // The Markdown output format ships with the theme: a project enables it
        // with "<output-format>md</output-format>" alone, without having to know
        // that a second extension exists.
        $this->markdownExtension()->load($configs, $container);

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
                        // Markdown is rendered beside the HTML unless a project
                        // opts out with render-markdown="false".
                        'render_markdown' => $this->getConfigValue($configs, 'render_markdown', 'true'),
                        // Still on: tools read the published reStructuredText
                        // today, and the Markdown that replaces it has only just
                        // appeared. Switching this off ends the transition -- the
                        // "view source" entry then points at the repository
                        // instead, which is where the source lives anyway.
                        'copy_sources' => $this->getConfigValue($configs, 'copy_sources', 'true'),
                        'project_home' => $this->getConfigValue($configs, 'project_home', ''),
                        'project_contact' => $this->getConfigValue($configs, 'project_contact', ''),
                        'project_repository' => $this->getConfigValue($configs, 'project_repository', ''),
                        'project_issues' => $this->getConfigValue($configs, 'project_issues', ''),
                        'report_issue' => $this->getConfigValue($configs, 'report_issue', ''),
                        'typo3_core_preferred' => $this->getConfigValue($configs, 'typo3_core_preferred', ''),
                        'confval_default' => $this->getConfigValue($configs, 'confval_default', 'Option'),
                        'disable_version_switch' => $this->getConfigValue($configs, 'disable_version_switch', ''),
                        'lint' => $this->getConfigValue($configs, 'lint', 'false'),
                        'lint_discouraged_phrases' => $this->getConfigValue($configs, 'lint_discouraged_phrases', ''),
                        'lint_heading_allowed_words' => $this->getConfigValue($configs, 'lint_heading_allowed_words', ''),
                    ],
                ],
            );
            $container->setDefinition(Typo3DocsThemeSettings::class, $definition);

            $definition = new Definition(
                Typo3DocsInputSettings::class,
            );
            $container->setDefinition(Typo3DocsInputSettings::class, $definition);
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
        // Before this extension's own templates, not after: prependExtensionConfig
        // puts the last caller first, and a node renderer registered from a
        // template wins by is_a(), so the first match decides. The Markdown
        // package maps InlineCompoundNode, which every link node extends --
        // FileInlineNode among them. Registered the other way round, the generic
        // map would swallow the file text role and it would lose its backticks.
        $this->markdownExtension()->prepend($container);

        $container->prependExtensionConfig('guides', [
            'themes' => [
                'typo3docs' => [
                    'extends' => 'bootstrap',
                    'templates' => $this->getTemplates(),
                ],
            ],

            'templates' => [
                template(CodeInlineNode::class, 'inline/textroles/code.html.twig'),
                template(ComposerInlineNode::class, 'inline/textroles/composer.html.twig'),
                template(FileInlineNode::class, 'inline/textroles/file.html.twig'),
                template(CodeInlineNode::class, 'inline/textroles/code.md.twig', 'md'),
                template(ComposerInlineNode::class, 'inline/textroles/composer.md.twig', 'md'),
                template(FileInlineNode::class, 'inline/textroles/file.md.twig', 'md'),
                template(YoutubeNode::class, 'body/directive/youtube.md.twig', 'md'),
                template(UmlNode::class, 'body/uml.md.twig', 'md'),
                template(PhpComponentNode::class, 'body/directive/php/component.md.twig', 'md'),
                template(PhpMethodNode::class, 'body/directive/php/method.md.twig', 'md'),
                template(PhpPropertyNode::class, 'body/directive/php/property.md.twig', 'md'),
                template(PhpConstNode::class, 'body/directive/php/const.md.twig', 'md'),
                template(PhpCaseNode::class, 'body/directive/php/case.md.twig', 'md'),
                template(PhpGlobalNode::class, 'body/directive/php/global.md.twig', 'md'),
                template(PhpNamespaceNode::class, 'body/directive/php/namespace.md.twig', 'md'),
                template(PhpModifierNode::class, 'body/directive/php/modifier.md.twig', 'md'),
                template(MemberNameNode::class, 'body/directive/php/memberName.md.twig', 'md'),
            ],
        ]);
    }

    /**
     * The Markdown output format is part of the theme rather than a separate
     * opt-in extension. It registers its own template paths and renderer, and
     * stays inert until a project asks for the "md" output format.
     */
    private function markdownExtension(): Typo3DocsThemeMdExtension
    {
        return new Typo3DocsThemeMdExtension();
    }

    /**
     * Whether this project wants Markdown beside its HTML. On unless it says
     * otherwise, so an author who cares can switch it off with
     * render-markdown="false".
     *
     * The value is read back from the settings definition built in load(),
     * which is the only place the guides.xml attributes are available. Note
     * that "false" is a non-empty string and therefore truthy in PHP, so the
     * off values are matched explicitly rather than by truthiness -- the same
     * trap the Twig templates carry for their own flags.
     */
    private function markdownRequested(ContainerBuilder $container): bool
    {
        if (!$container->hasDefinition(Typo3DocsThemeSettings::class)) {
            return true;
        }

        $arguments = $container->getDefinition(Typo3DocsThemeSettings::class)->getArguments();
        $settings = $arguments['$settings'] ?? [];
        if (!is_array($settings)) {
            return true;
        }

        $value = $settings['render_markdown'] ?? 'true';
        if (!is_scalar($value)) {
            return true;
        }

        $value = strtolower(trim((string) $value));

        return !in_array($value, ['', 'false', '0', 'off', 'no'], true);
    }

    /**
     * Build the template search path in priority order:
     *
     * 1. Docker volume mount at /templates (highest priority)
     * 2. Project-bundled templates at /project/resources/custom-templates
     * 3. Built-in theme templates (fallback)
     *
     * @return list<string>
     */
    private function getTemplates(): array
    {
        $templates = [];

        if (is_dir('/templates') && is_readable('/templates')) {
            $templates[] = '/templates';
        }

        if (is_dir('/project/resources/custom-templates') && is_readable('/project/resources/custom-templates')) {
            $templates[] = '/project/resources/custom-templates';
        }

        $templates[] = dirname(__DIR__, 2) . '/resources/template';

        return $templates;
    }

    /**
     * Remove the base library's FigureDirective in favor of our custom implementation
     * that supports zoom functionality.
     */
    public function process(ContainerBuilder $container): void
    {
        // Remove the base library's FigureDirective to let our custom one take over
        if ($container->hasDefinition(BaseFigureDirective::class)) {
            $container->removeDefinition(BaseFigureDirective::class);
        }

        // Same for the index directive: the base one parses ".. index::" and
        // returns null, so the terms are lost. Ours keeps them.
        if ($container->hasDefinition(BaseIndexDirective::class)) {
            $container->removeDefinition(BaseIndexDirective::class);
        }

        if ($this->markdownRequested($container)) {
            $this->appendOutputFormat($container, 'md');
        }

        // Every manual gets a table of contents, and the Core Changelog gets
        // an index of its entries on top. @see TocJsonRenderer,
        // ChangelogJsonRenderer
        $this->appendOutputFormat($container, 'tocjson');
        if ($this->isChangelog($container)) {
            $this->appendOutputFormat($container, 'changelogjson');
        }

        if ($this->markdownRequested($container)) {
            $this->markdownExtension()->markUnsupportedNodes($container);
        }
    }

    /**
     * Add an output format to whatever the project configured.
     *
     * This cannot be done by prepending "output_format" to the guides
     * configuration: any explicitly provided value replaces the default
     * ["html", "interlink"] rather than extending it, so a project that
     * configures nothing would end up rendering the added format and no HTML.
     *
     * By the time the container is compiled the settings object carries the
     * final list, so appending to it leaves every other format untouched --
     * a project rendering only "singlepage" or only "rst" keeps doing that,
     * and gains the added one beside it.
     */
    private function appendOutputFormat(ContainerBuilder $container, string $format): void
    {
        if (!$container->hasDefinition(SettingsManager::class)) {
            return;
        }

        $definition = $container->getDefinition(SettingsManager::class);
        $methodCalls = $definition->getMethodCalls();

        foreach ($methodCalls as $index => $call) {
            if (!is_array($call) || ($call[0] ?? null) !== 'setProjectSettings') {
                continue;
            }

            $arguments = $call[1] ?? null;
            $projectSettings = is_array($arguments) ? ($arguments[0] ?? null) : null;
            if (!$projectSettings instanceof ProjectSettings) {
                continue;
            }

            $outputFormats = $projectSettings->getOutputFormats();
            if (in_array($format, $outputFormats, true)) {
                return;
            }

            $outputFormats[] = $format;
            $projectSettings->setOutputFormats($outputFormats);

            $methodCalls[$index] = ['setProjectSettings', [$projectSettings]];
            $definition->setMethodCalls($methodCalls);

            return;
        }
    }

    /**
     * Whether this is the TYPO3 Core Changelog, which is rendered with an index
     * of its entries that no other manual gets.
     *
     * Recognised by its interlink shortcode, the same marker the rest of the
     * theme uses for changelog-specific behaviour. The manual cannot ask for
     * the index itself: its guides.xml lives in typo3/cms-core, and requiring
     * the format to be configured there would make a Core patch the price of a
     * documentation artifact.
     */
    private function isChangelog(ContainerBuilder $container): bool
    {
        if (!$container->hasDefinition(Typo3DocsThemeSettings::class)) {
            return false;
        }

        $arguments = $container->getDefinition(Typo3DocsThemeSettings::class)->getArguments();
        $settings = $arguments['$settings'] ?? [];

        return is_array($settings) && ($settings['interlink_shortcode'] ?? '') === 'changelog';
    }
}
