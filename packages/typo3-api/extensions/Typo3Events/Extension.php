<?php

declare(strict_types=1);

namespace T3Docs\Typo3Api\Events;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\PhpFileLoader;
use T3Docs\Typo3Api\Events\Reflection\DispatchedBy;
use T3Docs\Typo3Api\Events\Reflection\DispatchedEvents;

/**
 * Lists the PSR-14 events that a method or function dispatches on its page
 * in the API documentation, and the methods and functions that dispatch an
 * event on the page of the event.
 */
final class Extension extends \phpDocumentor\Extension\Extension
{
    public function getAlias(): string
    {
        return 'typo3-events';
    }

    public function getTemplatePath(): string
    {
        return __DIR__ . '/Resources/templates';
    }

    /** @return string[] */
    public function getMetadataKeys(): array
    {
        return [DispatchedEvents::KEY, DispatchedBy::KEY];
    }

    /** @param array<mixed> $configs */
    public function load(array $configs, ContainerBuilder $container): void
    {
        $loader = new PhpFileLoader($container, new FileLocator(__DIR__ . '/Resources/config'));
        $loader->load('services.php');
    }
}
