<?php

declare(strict_types=1);

use Brotkrueml\TwigCodeHighlight\Extension as CodeHighlight;
use phpDocumentor\Guides\Event\PostCollectFilesForParsingEvent;
use phpDocumentor\Guides\Event\PostProjectNodeCreated;
use phpDocumentor\Guides\Event\PostRenderProcess;
use phpDocumentor\Guides\Event\PreParseProcess;
use phpDocumentor\Guides\Graphs\Renderer\PlantumlServerRenderer;
use phpDocumentor\Guides\ReferenceResolvers\Interlink\InventoryRepository;
use phpDocumentor\Guides\RestructuredText\Directives\BaseDirective;
use phpDocumentor\Guides\RestructuredText\Directives\SubDirective;
use phpDocumentor\Guides\RestructuredText\Parser\Interlink\InterlinkParser;
use phpDocumentor\Guides\RestructuredText\Parser\Productions\DirectiveContentRule;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

use T3Docs\Typo3DocsTheme\Directives\RawDirective;
use T3Docs\Typo3DocsTheme\EventListeners\AddThemeSettingsToProjectNode;
use T3Docs\Typo3DocsTheme\EventListeners\CopyResources;
use T3Docs\Typo3DocsTheme\Directives\GroupTabDirective;

use T3Docs\Typo3DocsTheme\Directives\T3FieldListTableDirective;
use T3Docs\Typo3DocsTheme\Directives\YoutubeDirective;
use T3Docs\Typo3DocsTheme\EventListeners\IgnoreLocalizationsFolders;
use T3Docs\Typo3DocsTheme\EventListeners\TestingModeActivator;
use T3Docs\Typo3DocsTheme\Inventory\Typo3InventoryRepository;
use T3Docs\Typo3DocsTheme\Parser\ExtendedInterlinkParser;
use T3Docs\Typo3DocsTheme\Renderer\DecoratingPlantumlRenderer;
use T3Docs\Typo3DocsTheme\TextRoles\FluidTextTextRole;
use T3Docs\Typo3DocsTheme\TextRoles\HtmlTextTextRole;
use T3Docs\Typo3DocsTheme\TextRoles\InputTextTextRole;
use T3Docs\Typo3DocsTheme\TextRoles\IssueReferenceTextRole;
use T3Docs\Typo3DocsTheme\TextRoles\JavaScriptTextRole;
use T3Docs\Typo3DocsTheme\TextRoles\OutputTextTextRole;
use T3Docs\Typo3DocsTheme\TextRoles\PhpTextRole;
use T3Docs\Typo3DocsTheme\TextRoles\RestructuredTextTextRole;
use T3Docs\Typo3DocsTheme\TextRoles\ShellTextTextRole;
use T3Docs\Typo3DocsTheme\TextRoles\TSconfigTextRole;
use T3Docs\Typo3DocsTheme\TextRoles\TypeScriptTextRole;
use T3Docs\Typo3DocsTheme\TextRoles\TypoScriptTextTextRole;
use T3Docs\Typo3DocsTheme\TextRoles\XmlTextTextRole;
use T3Docs\Typo3DocsTheme\TextRoles\YamlTextTextRole;
use T3Docs\Typo3DocsTheme\Twig\TwigExtension;

use function Symfony\Component\DependencyInjection\Loader\Configurator\param;
use function Symfony\Component\DependencyInjection\Loader\Configurator\service;

return static function (ContainerConfigurator $container): void {
    $container->services()
        ->defaults()
        ->autowire()
        ->autoconfigure()
        ->instanceof(SubDirective::class)
        ->bind('$startingRule', service(DirectiveContentRule::class))
        ->instanceof(BaseDirective::class)
        ->tag('phpdoc.guides.directive')
        ->set(TwigExtension::class)
        ->tag('twig.extension')
        ->autowire()
        ->set(IssueReferenceTextRole::class)
        ->tag('phpdoc.guides.parser.rst.text_role')
        ->set(phpDocumentor\Guides\ReferenceResolvers\Interlink\DefaultInventoryLoader::class)
        ->set(InventoryRepository::class, Typo3InventoryRepository::class)
        ->arg('$inventoryConfigs', param('phpdoc.guides.inventories'))
        ->set(InterlinkParser::class, ExtendedInterlinkParser::class)
        ->set(FluidTextTextRole::class)
        ->tag('phpdoc.guides.parser.rst.text_role')
        ->set(HtmlTextTextRole::class)
        ->tag('phpdoc.guides.parser.rst.text_role')
        ->set(InputTextTextRole::class)
        ->tag('phpdoc.guides.parser.rst.text_role')
        ->set(JavaScriptTextRole::class)
        ->tag('phpdoc.guides.parser.rst.text_role')
        ->set(OutputTextTextRole::class)
        ->tag('phpdoc.guides.parser.rst.text_role')
        ->set(PhpTextRole::class)
        ->tag('phpdoc.guides.parser.rst.text_role')
        ->set(FluidTextTextRole::class)
        ->tag('phpdoc.guides.parser.rst.text_role')
        ->set(PhpTextRole::class)
        ->tag('phpdoc.guides.parser.rst.text_role')
        ->set(RestructuredTextTextRole::class)
        ->tag('phpdoc.guides.parser.rst.text_role')
        ->set(ShellTextTextRole::class)
        ->tag('phpdoc.guides.parser.rst.text_role')
        ->set(TSconfigTextRole::class)
        ->tag('phpdoc.guides.parser.rst.text_role')
        ->set(TypeScriptTextRole::class)
        ->tag('phpdoc.guides.parser.rst.text_role')
        ->set(TypoScriptTextTextRole::class)
        ->tag('phpdoc.guides.parser.rst.text_role')
        ->set(XmlTextTextRole::class)
        ->tag('phpdoc.guides.parser.rst.text_role')
        ->set(YamlTextTextRole::class)
        ->tag('phpdoc.guides.parser.rst.text_role')


        ->set(DecoratingPlantumlRenderer::class)
        ->decorate(PlantumlServerRenderer::class)
        ->public()

        ->set(GroupTabDirective::class)
        ->set(RawDirective::class)
        ->set(T3FieldListTableDirective::class)
        ->set(YoutubeDirective::class)
        ->set(CodeHighlight::class)
        ->arg('$languageAliases', [
            'none' => 'plaintext',
            'text' => 'plaintext',
        ])
        ->arg('$additionalLanguages', [
            ['typoscript', __DIR__ . '/../languages/typoscript.json'],
            ['rst', __DIR__ . '/../languages/rst.json'],
        ])
        ->arg('$classes', 'code-block')
        ->tag('twig.extension')
        ->autowire()

        // Register Event Listeners
        ->set(AddThemeSettingsToProjectNode::class)
        ->tag('event_listener', ['event' => PostProjectNodeCreated::class])

        ->set(CopyResources::class)
        ->tag('event_listener', ['event' => PostRenderProcess::class])

        ->set(IgnoreLocalizationsFolders::class)
        ->tag('event_listener', ['event' => PostCollectFilesForParsingEvent::class])

        ->set(TestingModeActivator::class)
        ->tag('event_listener', ['event' => PreParseProcess::class]);
};
