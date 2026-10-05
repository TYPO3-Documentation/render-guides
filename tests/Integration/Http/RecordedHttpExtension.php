<?php

declare(strict_types=1);

namespace T3Docs\Typo3DocsTheme\Integration\Http;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use T3Docs\Typo3DocsTheme\Api\Typo3ApiService;
use T3Docs\VersionHandling\Packagist\PackagistService;

/**
 * Makes a render in the integration tests read the network from recordings.
 * @see RecordedResponses
 *
 * A compiler pass, so that it replaces what the other extensions registered.
 */
final class RecordedHttpExtension extends Extension implements CompilerPassInterface
{
    public function __construct(
        private readonly string $directory,
    ) {}

    /** @param array<mixed> $configs */
    public function load(array $configs, ContainerBuilder $container): void {}

    public function process(ContainerBuilder $container): void
    {
        $container->register(RecordedResponses::class, RecordedResponses::class)
            ->setArguments([$this->directory]);
        $container->register(RecordedHttpResponder::class, RecordedHttpResponder::class)
            ->setArguments([new Reference(RecordedResponses::class)]);
        $container->register(HttpClientInterface::class, MockHttpClient::class)
            ->setArguments([new Reference(RecordedHttpResponder::class)]);

        foreach ([Typo3ApiService::class, PackagistService::class] as $service) {
            if ($container->hasDefinition($service)) {
                $container->getDefinition($service)
                    ->addMethodCall('fetchWith', [new Reference(RecordedResponses::class)]);
            }
        }
    }

    public function getAlias(): string
    {
        return 'recorded_http';
    }
}
