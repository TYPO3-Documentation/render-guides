<?php

declare(strict_types=1);

namespace T3Docs\Typo3Api\Json;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\PhpFileLoader;
use T3Docs\Typo3Api\Json\Reflection\DocBlockTypes;

/**
 * Writes a class, interface, trait, or enum with its members as JSON, so
 * that a manual can show it without generating reStructuredText from it.
 */
final class Extension extends \phpDocumentor\Extension\Extension
{
    public function getAlias(): string
    {
        return 'typo3-json';
    }

    public function getTemplatePath(): string
    {
        return __DIR__ . '/Resources/templates';
    }

    /** @return string[] */
    public function getMetadataKeys(): array
    {
        return [DocBlockTypes::KEY];
    }

    /** @param array<mixed> $configs */
    public function load(array $configs, ContainerBuilder $container): void
    {
        $loader = new PhpFileLoader($container, new FileLocator(__DIR__ . '/Resources/config'));
        $loader->load('services.php');
    }
}
