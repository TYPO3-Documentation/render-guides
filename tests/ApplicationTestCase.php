<?php

declare(strict_types=1);

namespace T3Docs\Typo3DocsTheme;

use phpDocumentor\Guides\Bootstrap\DependencyInjection\BootstrapExtension;
use phpDocumentor\Guides\Cli\DependencyInjection\ApplicationExtension;
use phpDocumentor\Guides\Cli\DependencyInjection\ContainerFactory;
use phpDocumentor\Guides\Markdown\DependencyInjection\MarkdownExtension;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\DependencyInjection\Extension\ExtensionInterface;
use T3Docs\GuidesExtension\DependencyInjection\TestExtension;
use T3Docs\GuidesPhpDomain\DependencyInjection\GuidesPhpDomainExtension;
use T3Docs\Typo3DocsTheme\DependencyInjection\Typo3DocsThemeExtension;

abstract class ApplicationTestCase extends TestCase
{
    private Container|null $container = null;

    public function getContainer(): Container
    {
        if ($this->container === null) {
            $this->prepareContainer();
        }

        return $this->container;
    }

    /**
     * @param array<string, array<mixed>> $configuration
     * @param ExtensionInterface $extraExtensions
     *
     * @phpstan-assert Container $this->container
     */
    protected function prepareContainer(string|null $configurationFile = null, array $configuration = [], array $extraExtensions = []): void
    {
        $containerFactory = new ContainerFactory([
            new ApplicationExtension(),
            new TestExtension(),
            new MarkdownExtension(),
            new BootstrapExtension(),
            new Typo3DocsThemeExtension(),
            new GuidesPhpDomainExtension(),
            ...$extraExtensions,
        ]);

        foreach ($configuration as $extension => $extensionConfig) {
            $containerFactory->loadExtensionConfig($extension, $extensionConfig);
        }

        if ($configurationFile !== null) {
            $containerFactory->addConfigFile($configurationFile);
        }

        $this->container = $containerFactory->create(dirname(__DIR__) . '/vendor');

        // Sets a dedicated environment variable so that i.e. the TwigExtension can
        // detect if it's being run within CI AND a PHPUnit testcase, to prevent using
        // Azure URLs but instead local URLs for assets.
        $_ENV['CI_PHPUNIT'] = true;
    }
}
