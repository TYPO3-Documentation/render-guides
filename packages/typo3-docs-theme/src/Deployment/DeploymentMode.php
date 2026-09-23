<?php

declare(strict_types=1);

namespace T3Docs\Typo3DocsTheme\Deployment;

use function getenv;
use function strlen;

/**
 * Whether a render is the one that goes to docs.typo3.org.
 *
 * It is, when it runs in our GitHub Action with the version of the pushed
 * Docker image at hand: that render links its assets to the CDN, and its pages
 * may reach the files of docs.typo3.org as their own. Every other render --
 * local, or in a manual's own pipeline -- is served from somewhere else and
 * cannot.
 */
final class DeploymentMode
{
    private readonly string $azureEdgeUri;

    public function __construct()
    {
        $inAction = strlen((string) getenv('GITHUB_ACTIONS')) > 0
            && strlen((string) getenv('TYPO3AZUREEDGEURIVERSION')) > 0
            // Not while the tests run, which would else compare CDN paths.
            && !isset($_ENV['CI_PHPUNIT']);

        $this->azureEdgeUri = $inAction
            ? 'https://cdn.typo3.com/typo3documentation/theme/typo3-docs-theme/' . getenv('TYPO3AZUREEDGEURIVERSION') . '/'
            : '';
    }

    public function isForDeployment(): bool
    {
        return $this->azureEdgeUri !== '';
    }

    /** The CDN the assets are linked to, empty for a render that keeps them local. */
    public function azureEdgeUri(): string
    {
        return $this->azureEdgeUri;
    }
}
