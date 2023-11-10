<?php

declare(strict_types=1);

namespace T3Docs\PhpDomain\DependencyInjection;

use function dirname;
use function phpDocumentor\Guides\DependencyInjection\template;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;

use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;

use Symfony\Component\DependencyInjection\Loader\PhpFileLoader;
use T3Docs\PhpDomain\Nodes\FullyQualifiedNameNode;
use T3Docs\PhpDomain\Nodes\MethodNameNode;

use T3Docs\PhpDomain\Nodes\PhpComponentNode;
use T3Docs\PhpDomain\Nodes\PhpMethodNode;
use T3Docs\PhpDomain\Nodes\PhpNamespaceNode;

final class PhpDomainExtension extends Extension implements PrependExtensionInterface
{
    /** @param mixed[] $configs */
    public function load(array $configs, ContainerBuilder $container): void
    {
        $loader = new PhpFileLoader(
            $container,
            new FileLocator(dirname(__DIR__, 2) . '/resources/config'),
        );
        $loader->load('php-domain.php');
    }

    public function prepend(ContainerBuilder $container): void
    {
        $container->prependExtensionConfig(
            'guides',
            [
                'base_template_paths' => [dirname(__DIR__, 2) . '/resources/template/html'],
                'templates' => [
                    template(FullyQualifiedNameNode::class, 'body/directive/php/fullyQualifiedName.html.twig'),
                    template(PhpComponentNode::class, 'body/directive/php/component.html.twig'),
                    template(PhpNamespaceNode::class, 'body/directive/php/namespace.html.twig'),
                    template(PhpMethodNode::class, 'body/directive/php/method.html.twig'),
                    template(MethodNameNode::class, 'body/directive/php/methodName.html.twig'),
                ],
            ],
        );
    }
}
