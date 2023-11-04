<?php

declare(strict_types=1);

use phpDocumentor\Guides\NodeRenderers\TemplateNodeRenderer;
use phpDocumentor\Guides\RestructuredText\Directives\BaseDirective;
use phpDocumentor\Guides\RestructuredText\Directives\SubDirective;
use phpDocumentor\Guides\RestructuredText\Parser\Productions\DirectiveContentRule;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

use function Symfony\Component\DependencyInjection\Loader\Configurator\service;

use T3Docs\PhpDomain\Directives\Php\InterfaceDirective;
use T3Docs\PhpDomain\Nodes\PhpComponentNode;

return static function (ContainerConfigurator $container): void {
    $container->services()
        ->defaults()
        ->autowire()
        ->autoconfigure()
        ->instanceof(SubDirective::class)
        ->bind('$startingRule', service(DirectiveContentRule::class))
        ->instanceof(BaseDirective::class)
        ->tag('phpdoc.guides.directive')
        ->set(InterfaceDirective::class)

        ->set('phpdoc.guides.', TemplateNodeRenderer::class)
        ->tag('phpdoc.guides.noderenderer.html')
        ->arg('$template', 'body/directive/php/component.html.twig')
        ->arg('$nodeClass', PhpComponentNode::class)

    ;
};
