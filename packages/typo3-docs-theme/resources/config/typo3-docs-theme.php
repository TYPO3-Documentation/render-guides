<?php

declare(strict_types=1);

use Brotkrueml\TwigCodeHighlight\Extension as CodeHighlight;
use phpDocumentor\Guides\Event\PostCollectFilesForParsingEvent;
use phpDocumentor\Guides\Event\PostParseDocument;
use phpDocumentor\Guides\Event\PostProjectNodeCreated;
use phpDocumentor\Guides\Event\PostRenderProcess;
use phpDocumentor\Guides\Event\PreParseProcess;
use phpDocumentor\Guides\Graphs\Renderer\PlantumlServerRenderer;
use phpDocumentor\Guides\ReferenceResolvers\DelegatingReferenceResolver;
use phpDocumentor\Guides\ReferenceResolvers\Interlink\InventoryRepository;
use phpDocumentor\Guides\RestructuredText\Directives\BaseDirective;
use phpDocumentor\Guides\RestructuredText\Directives\SubDirective;
use phpDocumentor\Guides\RestructuredText\Parser\Interlink\InterlinkParser;
use phpDocumentor\Guides\RestructuredText\Parser\Productions\DirectiveContentRule;
use phpDocumentor\Guides\RestructuredText\Parser\Productions\DocumentRule;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use T3Docs\Typo3DocsTheme\Api\Typo3ApiService;
use T3Docs\Typo3DocsTheme\Compiler\NodeTransformers\AttachFileObjectsToFileTextRoleTransformer;
use T3Docs\Typo3DocsTheme\Compiler\NodeTransformers\CollectFileObjectsTransformer;
use T3Docs\Typo3DocsTheme\Compiler\NodeTransformers\CollectPrefixLinkTargetsTransformer;
use T3Docs\Typo3DocsTheme\Compiler\NodeTransformers\ConfvalMenuNodeTransformer;
use T3Docs\Typo3DocsTheme\Compiler\NodeTransformers\RedirectsNodeTransformer;
use T3Docs\Typo3DocsTheme\Compiler\NodeTransformers\RemoveInterlinkSelfReferencesFromCrossReferenceNodeTransformer;
use T3Docs\Typo3DocsTheme\Compiler\NodeTransformers\ReplacePermalinksNodeTransformer;
use T3Docs\Typo3DocsTheme\Compiler\NodeTransformers\Typo3TalkNodeTransformer;
use T3Docs\Typo3DocsTheme\Directives\ConfvalMenuDirective;
use T3Docs\Typo3DocsTheme\Directives\DirectoryTreeDirective;
use T3Docs\Typo3DocsTheme\Directives\FigureDirective;
use T3Docs\Typo3DocsTheme\Directives\ImageDirective;
use T3Docs\Typo3DocsTheme\Directives\GlossaryDirective;
use T3Docs\Typo3DocsTheme\Directives\GroupTabDirective;
use T3Docs\Typo3DocsTheme\Directives\IncludeDirective;
use T3Docs\Typo3DocsTheme\Directives\LiteralincludeDirective;
use T3Docs\Typo3DocsTheme\Directives\MainMenuJsonDirective;
use T3Docs\Typo3DocsTheme\Directives\RawDirective;
use T3Docs\Typo3DocsTheme\Directives\SiteSetSettingsDirective;
use T3Docs\Typo3DocsTheme\Directives\T3FieldListTableDirective;
use T3Docs\Typo3DocsTheme\Directives\Typo3FileDirective;
use T3Docs\Typo3DocsTheme\Directives\Typo3TalkDirective;
use T3Docs\Typo3DocsTheme\Directives\ViewHelperDirective;
use T3Docs\Typo3DocsTheme\Directives\YoutubeDirective;
use T3Docs\Typo3DocsTheme\EventListeners\AddThemeSettingsToProjectNode;
use T3Docs\Typo3DocsTheme\EventListeners\CopyResources;
use T3Docs\Typo3DocsTheme\EventListeners\IgnoreLocalizationsFolders;
use T3Docs\Typo3DocsTheme\EventListeners\OriginalFileNameSetter;
use T3Docs\Typo3DocsTheme\EventListeners\TestingModeActivator;
use T3Docs\Typo3DocsTheme\Inventory\DefaultInterlinkParser;
use T3Docs\Typo3DocsTheme\Inventory\DefaultInventoryUrlBuilder;
use T3Docs\Typo3DocsTheme\Inventory\InterlinkParserInterface;
use T3Docs\Typo3DocsTheme\Inventory\InventoryUrlBuilderInterface;
use T3Docs\Typo3DocsTheme\Inventory\Typo3InventoryRepository;
use T3Docs\Typo3DocsTheme\Inventory\Typo3VersionService;
use T3Docs\Typo3DocsTheme\Parser\ExtendedInterlinkParser;
use T3Docs\Typo3DocsTheme\Parser\Productions\FieldList\EditOnGitHubFieldListItemRule;
use T3Docs\Typo3DocsTheme\Parser\Productions\FieldList\TemplateFieldListItemRule;
use T3Docs\Typo3DocsTheme\ReferenceResolvers\FileReferenceResolver;
use T3Docs\Typo3DocsTheme\ReferenceResolvers\ObjectsInventory\ObjectInventory;
use T3Docs\Typo3DocsTheme\Renderer\DecoratingPlantumlRenderer;
use T3Docs\Typo3DocsTheme\Renderer\MainMenuJsonRenderer;
use T3Docs\Typo3DocsTheme\Renderer\NodeRenderer\MainMenuJsonDocumentRenderer;
use T3Docs\Typo3DocsTheme\TextRoles\ApiClassTextRole;
use T3Docs\Typo3DocsTheme\TextRoles\ComposerTextRole;
use T3Docs\Typo3DocsTheme\TextRoles\CssTextRole;
use T3Docs\Typo3DocsTheme\TextRoles\FileTextRole;
use T3Docs\Typo3DocsTheme\TextRoles\FluidTextTextRole;
use T3Docs\Typo3DocsTheme\TextRoles\HtmlTextTextRole;
use T3Docs\Typo3DocsTheme\TextRoles\InputTextTextRole;
use T3Docs\Typo3DocsTheme\TextRoles\IssueReferenceTextRole;
use T3Docs\Typo3DocsTheme\TextRoles\JavaScriptTextRole;
use T3Docs\Typo3DocsTheme\TextRoles\OutputTextTextRole;
use T3Docs\Typo3DocsTheme\TextRoles\PhpTextRole;
use T3Docs\Typo3DocsTheme\TextRoles\RestructuredTextTextRole;
use T3Docs\Typo3DocsTheme\TextRoles\ScssTextRole;
use T3Docs\Typo3DocsTheme\TextRoles\ShellTextTextRole;
use T3Docs\Typo3DocsTheme\TextRoles\SqlTextRole;
use T3Docs\Typo3DocsTheme\TextRoles\T3extTextRole;
use T3Docs\Typo3DocsTheme\TextRoles\T3srcTextRole;
use T3Docs\Typo3DocsTheme\TextRoles\TSconfigTextRole;
use T3Docs\Typo3DocsTheme\TextRoles\TypeScriptTextRole;
use T3Docs\Typo3DocsTheme\TextRoles\TypoScriptTextTextRole;
use T3Docs\Typo3DocsTheme\TextRoles\ViewhelperArgumentTextRole;
use T3Docs\Typo3DocsTheme\TextRoles\ViewhelperTextRole;
use T3Docs\Typo3DocsTheme\TextRoles\XmlTextTextRole;
use T3Docs\Typo3DocsTheme\TextRoles\YamlTextTextRole;
use T3Docs\Typo3DocsTheme\Twig\TwigExtension;
use T3Docs\VersionHandling\Packagist\PackagistService;

use function Symfony\Component\DependencyInjection\Loader\Configurator\param;
use function Symfony\Component\DependencyInjection\Loader\Configurator\service;
use function Symfony\Component\DependencyInjection\Loader\Configurator\tagged_iterator;

return static function (ContainerConfigurator $container): void {
    $container->services()
        ->defaults()
        ->autowire()
        ->autoconfigure()
        ->instanceof(SubDirective::class)
        ->bind('$startingRule', service(DirectiveContentRule::class))
        ->instanceof(BaseDirective::class)
        ->tag('phpdoc.guides.directive')
        ->set(AttachFileObjectsToFileTextRoleTransformer::class)
        ->tag('phpdoc.guides.compiler.nodeTransformers')
        ->set(RedirectsNodeTransformer::class)
        ->tag('phpdoc.guides.compiler.nodeTransformers')
        ->set(ReplacePermalinksNodeTransformer::class)
        ->tag('phpdoc.guides.compiler.nodeTransformers')
        ->set(CollectPrefixLinkTargetsTransformer::class)
        ->tag('phpdoc.guides.compiler.nodeTransformers')
        ->set(CollectFileObjectsTransformer::class)
        ->tag('phpdoc.guides.compiler.nodeTransformers')
        ->set(ConfvalMenuNodeTransformer::class)
        ->tag('phpdoc.guides.compiler.nodeTransformers')
        ->set(RemoveInterlinkSelfReferencesFromCrossReferenceNodeTransformer::class)
        ->tag('phpdoc.guides.compiler.nodeTransformers')
        ->set(Typo3TalkNodeTransformer::class)
        ->tag('phpdoc.guides.compiler.nodeTransformers')
        ->set(TwigExtension::class)
        ->tag('twig.extension')
        ->autowire()

        ->set(MainMenuJsonRenderer::class)
        ->tag(
            'phpdoc.renderer.typerenderer',
            [
                'noderender_tag' => 'phpdoc.guides.noderenderer.mainmenujson',
                'format' => 'mainmenujson',
            ],
        )
        ->set(MainMenuJsonDocumentRenderer::class)
        ->tag('phpdoc.guides.noderenderer.mainmenu')
        ->set(IssueReferenceTextRole::class)
        ->tag('phpdoc.guides.parser.rst.text_role')
        ->set(phpDocumentor\Guides\ReferenceResolvers\Interlink\DefaultInventoryLoader::class)
        ->set(DefaultInterlinkParser::class)
        ->set(DefaultInventoryUrlBuilder::class)
        ->alias(InterlinkParserInterface::class, DefaultInterlinkParser::class)
        ->alias(InventoryUrlBuilderInterface::class, DefaultInventoryUrlBuilder::class)
        ->set(InventoryRepository::class, Typo3InventoryRepository::class)
        ->arg('$inventoryConfigs', param('phpdoc.guides.inventories'))
        ->set(InterlinkParser::class, ExtendedInterlinkParser::class)
        ->set(\phpDocumentor\Guides\RestructuredText\TextRoles\ApiClassTextRole::class, ApiClassTextRole::class)
        ->tag('phpdoc.guides.parser.rst.text_role')
        ->set(ComposerTextRole::class)
        ->tag('phpdoc.guides.parser.rst.text_role')
        ->set(CssTextRole::class)
        ->tag('phpdoc.guides.parser.rst.text_role')
        ->set(FileTextRole::class)
        ->tag('phpdoc.guides.parser.rst.text_role')
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
        ->set(RestructuredTextTextRole::class)
        ->tag('phpdoc.guides.parser.rst.text_role')
        ->set(ScssTextRole::class)
        ->tag('phpdoc.guides.parser.rst.text_role')
        ->set(ShellTextTextRole::class)
        ->tag('phpdoc.guides.parser.rst.text_role')
        ->set(SqlTextRole::class)
        ->tag('phpdoc.guides.parser.rst.text_role')
        ->set(T3extTextRole::class)
        ->tag('phpdoc.guides.parser.rst.text_role')
        ->set(T3srcTextRole::class)
        ->tag('phpdoc.guides.parser.rst.text_role')
        ->set(TSconfigTextRole::class)
        ->tag('phpdoc.guides.parser.rst.text_role')
        ->set(TypeScriptTextRole::class)
        ->tag('phpdoc.guides.parser.rst.text_role')
        ->set(TypoScriptTextTextRole::class)
        ->tag('phpdoc.guides.parser.rst.text_role')
        ->set(ViewhelperTextRole::class)
        ->tag('phpdoc.guides.parser.rst.text_role', ['domain' => 'typo3'])
        ->set(ViewhelperArgumentTextRole::class)
        ->tag('phpdoc.guides.parser.rst.text_role', ['domain' => 'typo3'])
        ->set(XmlTextTextRole::class)
        ->tag('phpdoc.guides.parser.rst.text_role')
        ->set(YamlTextTextRole::class)
        ->tag('phpdoc.guides.parser.rst.text_role')

        ->set(TemplateFieldListItemRule::class)
        ->tag('phpdoc.guides.parser.rst.fieldlist')
        ->set(EditOnGitHubFieldListItemRule::class)
        ->tag('phpdoc.guides.parser.rst.fieldlist')

        ->set(DelegatingReferenceResolver::class)
        ->arg('$resolvers', tagged_iterator('phpdoc.guides.reference_resolver', defaultPriorityMethod: 'getPriority'))

        ->set(DecoratingPlantumlRenderer::class)
        ->decorate(PlantumlServerRenderer::class)
        ->public()

        ->set(ConfvalMenuDirective::class)
        ->set(DirectoryTreeDirective::class)
        ->set(FigureDirective::class)
        ->arg('$startingRule', service(DirectiveContentRule::class))
        ->set(ImageDirective::class)
        ->set(GlossaryDirective::class)
        ->set(GroupTabDirective::class)
        ->set(IncludeDirective::class)
        ->set(LiteralincludeDirective::class)
        ->set(MainMenuJsonDirective::class)
        ->set(RawDirective::class)
        ->set(SiteSetSettingsDirective::class)
        ->set(Typo3FileDirective::class)
        ->set(T3FieldListTableDirective::class)
        ->set(Typo3TalkDirective::class)
        ->set(ViewHelperDirective::class)
        ->arg('$startingRule', service(DocumentRule::class))
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

        ->set(PackagistService::class)
        ->set(Typo3VersionService::class)
        ->set(Typo3ApiService::class)

        ->set(ObjectInventory::class)

        ->set(FileReferenceResolver::class)
        ->tag('phpdoc.guides.reference_resolver')

        // Register Event Listeners
        ->set(AddThemeSettingsToProjectNode::class)
        ->tag('event_listener', ['event' => PostProjectNodeCreated::class])

        ->set(CopyResources::class)
        ->tag('event_listener', ['event' => PostRenderProcess::class])

        ->set(IgnoreLocalizationsFolders::class)
        ->tag('event_listener', ['event' => PostCollectFilesForParsingEvent::class])

        ->set(TestingModeActivator::class)
        ->tag('event_listener', ['event' => PreParseProcess::class])

        ->set(OriginalFileNameSetter::class)
        ->tag('event_listener', ['event' => PostParseDocument::class]);
};
