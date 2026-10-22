<?php

declare(strict_types=1);

namespace T3Docs\Typo3Api\Documentation;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\PhpFileLoader;
use T3Docs\Typo3Api\Documentation\Reflection\Documentation;

/**
 * Lists on the page of a class where the TYPO3 documentation speaks of it:
 * the section that documents it, the sections that mention it, and the
 * Changelog entries that name it.
 */
final class Extension extends \phpDocumentor\Extension\Extension
{
    public function getAlias(): string
    {
        return 'typo3-documentation';
    }

    public function getTemplatePath(): string
    {
        return __DIR__ . '/Resources/templates';
    }

    /** @return string[] */
    public function getMetadataKeys(): array
    {
        return [Documentation::KEY];
    }

    /** @param array<mixed> $configs */
    public function load(array $configs, ContainerBuilder $container): void
    {
        $loader = new PhpFileLoader($container, new FileLocator(__DIR__ . '/Resources/config'));
        $loader->load('services.php');
    }
}
