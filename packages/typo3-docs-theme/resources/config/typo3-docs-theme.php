<?php

declare(strict_types=1);

use Brotkrueml\TwigCodeHighlight\Extension as CodeHighlight;
use phpDocumentor\Guides\Event\PostRenderProcess;
use phpDocumentor\Guides\RestructuredText\Directives\BaseDirective;
use phpDocumentor\Guides\RestructuredText\Directives\SubDirective;
use phpDocumentor\Guides\RestructuredText\Parser\Productions\DirectiveContentRule;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

use T3Docs\Typo3DocsTheme\EventListeners\CopyStaticAssets;
use T3Docs\Typo3DocsTheme\Directives\GroupTabDirective;

use T3Docs\Typo3DocsTheme\Directives\T3FieldListTableDirective;
use T3Docs\Typo3DocsTheme\Directives\YoutubeDirective;
use T3Docs\Typo3DocsTheme\TextRoles\IssueReferenceTextRole;
use T3Docs\Typo3DocsTheme\Twig\TwigExtension;

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

        ->set(GroupTabDirective::class)
        ->set(T3FieldListTableDirective::class)
        ->set(YoutubeDirective::class)
        ->set(CodeHighlight::class)
        ->arg('$languageAliases', [
            'none' => 'plaintext',
            'text' => 'plaintext',
            'typoscript' => 'plaintext',
        ])
        ->arg('$classes', 'code-block')
        ->tag('twig.extension')
        ->autowire()

        ->set(CopyStaticAssets::class)
        ->tag('event_listener', ['event' => PostRenderProcess::class]);
};
