<?php

declare(strict_types=1);

namespace T3Docs\Typo3DocsTheme\Twig;

use League\Flysystem\Exception;
use LogicException;
use phpDocumentor\Guides\Nodes\DocumentTree\DocumentEntryNode;
use phpDocumentor\Guides\ReferenceResolvers\DocumentNameResolverInterface;
use phpDocumentor\Guides\RenderContext;
use phpDocumentor\Guides\Renderer\UrlGenerator\UrlGeneratorInterface;
use Psr\Log\LoggerInterface;
use RuntimeException;
use T3Docs\Typo3DocsTheme\Nodes\PageLinkNode;
use T3Docs\Typo3DocsTheme\Settings\Typo3DocsThemeSettings;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

final class TwigExtension extends AbstractExtension
{
    private string $typo3AzureEdgeURI = '';

    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly UrlGeneratorInterface $urlGenerator,
        private readonly Typo3DocsThemeSettings $themeSettings,
        private readonly DocumentNameResolverInterface $documentNameResolver,
    ) {
        if (getenv('CI') !== '' && !isset($_ENV['CI_PHPUNIT'])) {
            // CI gets special treatment, then we use a fixed URI for assets.
            // The environment variable 'TYPO3AZUREEDGEURIVERSION' is set during
            // the creation of our Docker image, and holds the last pushed version
            // number. This version number will then only be utilized in CI GitHub Action
            // executions, and sets links to resources/assets to a public CDN.
            // Outside CI (and for local development) all Assets are linked locally.
            // This is prevented when being run within PHPUnit.
            // TODO: Check in which GHA this is actually performed. Simulate with my own host.
            $this->typo3AzureEdgeURI = 'https://typo3.azureedge.net/typo3documentation/theme/render-guides/' . getenv('TYPO3AZUREEDGEURIVERSION');
        }
    }

    /** @return TwigFunction[] */
    public function getFunctions(): array
    {
        return [
            new TwigFunction('getRelativePath', $this->getRelativePath(...), ['needs_context' => true]),
            new TwigFunction('getPagerLinks', $this->getPagerLinks(...), ['is_safe' => ['html'], 'needs_context' => true]),
            new TwigFunction('getPrevNextLinks', $this->getPrevNextLinks(...), ['is_safe' => ['html'], 'needs_context' => true]),
            new TwigFunction('getSettings', $this->getSettings(...), ['is_safe' => ['html'], 'needs_context' => true]),
            new TwigFunction('copyDownload', $this->copyDownload(...), ['is_safe' => ['html'], 'needs_context' => true]),
        ];
    }

    /**
     * @param array{env: RenderContext} $context
     */
    public function getRelativePath(array $context, string $path): string
    {
        if ($this->typo3AzureEdgeURI !== '') {
            // CI gets special treatment, then we use a fixed URI for assets.
            return $this->typo3AzureEdgeURI . $path;
        } else {
            return $this->urlGenerator->generateInternalUrl($context['env'] ?? null, $path);
        }
    }

    /**
     * @param array{env: RenderContext} $context
     * @return string
     */
    public function copyDownload(
        array $context,
        string $sourcePath,
        string $targetPath
    ): string {
        $outputPath = $this->copyAsset($context['env'] ?? null, $sourcePath, $targetPath);
        $relativePath = $this->urlGenerator->generateInternalUrl($context['env'] ?? null, trim($outputPath, '/'));
        // make it relative so it plays nice with the base tag in the HEAD
        return $relativePath;
    }

    private function copyAsset(
        RenderContext|null $renderContext,
        string $sourcePath,
        string $targetPath
    ): string {
        if (!$renderContext instanceof RenderContext) {
            return $sourcePath;
        }

        $canonicalUrl = $this->documentNameResolver->canonicalUrl($renderContext->getDirName(), $sourcePath);
        $outputPath = $this->documentNameResolver->absoluteUrl(
            $renderContext->getDestinationPath(),
            $targetPath,
        );

        try {
            if ($renderContext->getOrigin()->has($sourcePath) === false) {
                $this->logger->error(
                    sprintf('Download not found "%s"', $sourcePath),
                    $renderContext->getLoggerInformation(),
                );

                return $outputPath;
            }

            $fileContents = $renderContext->getOrigin()->read($sourcePath);
            if ($fileContents === false) {
                $this->logger->error(
                    sprintf('Could not read download file "%s"', $sourcePath),
                    $renderContext->getLoggerInformation(),
                );

                return $outputPath;
            }

            $result = $renderContext->getDestination()->put($outputPath, $fileContents);
            if ($result === false) {
                $this->logger->error(
                    sprintf('Unable to write file "%s"', $outputPath),
                    $renderContext->getLoggerInformation(),
                );
            }
        } catch (LogicException | Exception $e) {
            $this->logger->error(
                sprintf('Unable to write file "%s", %s', $outputPath, $e->getMessage()),
                $renderContext->getLoggerInformation(),
            );
        }

        return $outputPath;
    }

    /**
     * @param array{env: RenderContext} $context
     * @return string
     */
    public function getSettings(array $context, string $key, string $default = ''): string
    {
        return $this->themeSettings->getSettings($key, $default);
    }

    /**
     * @param array{env: RenderContext} $context
     * @return list<PageLinkNode>
     */
    public function getPagerLinks(array $context): array
    {
        $renderContext = $this->getRenderContext($context);
        $documentEntries = [
            'prev' => $this->getPrevDocumentEntry($renderContext),
            'next' => $this->getNextDocumentEntry($renderContext),
            'top' => $this->getTopDocumentEntry($renderContext),
        ];
        return $this->getPageLinks($documentEntries, $renderContext);
    }

    /**
     * @param array{env: RenderContext} $context
     * @return list<PageLinkNode>
     */
    public function getPrevNextLinks(array $context): array
    {
        $renderContext = $this->getRenderContext($context);
        $documentEntries = [
            'prev' => $this->getPrevDocumentEntry($renderContext),
            'next' => $this->getNextDocumentEntry($renderContext),
        ];
        return $this->getPageLinks($documentEntries, $renderContext);
    }

    private function getNextDocumentEntry(RenderContext $renderContext): DocumentEntryNode|null
    {
        $current = $renderContext->getCurrentFileName();
        $allDocuments = $renderContext->getProjectNode()->getAllDocumentEntries();

        $found = false;
        foreach ($allDocuments as $document) {
            if ($found) {
                // Next hit after the document itself
                return $document;
            }
            if ($document->getFile() === $current) {
                $found = true;
            }
        }
        return null;
    }

    private function getPrevDocumentEntry(RenderContext $renderContext): DocumentEntryNode|null
    {
        $current = $renderContext->getCurrentFileName();
        $allDocuments = $renderContext->getProjectNode()->getAllDocumentEntries();

        $prev = null;
        foreach ($allDocuments as $document) {
            if ($document->getFile() === $current) {
                return $prev;
            }
            $prev = $document;
        }
        return null;
    }

    private function getTopDocumentEntry(RenderContext $renderContext): DocumentEntryNode
    {
        return $renderContext->getProjectNode()->getRootDocumentEntry();
    }

    /** @param array{env: RenderContext} $context */
    private function getRenderContext(array $context): RenderContext
    {
        $renderContext = $context['env'] ?? null;
        if (!$renderContext instanceof RenderContext) {
            throw new RuntimeException('Render context must be set in the twig global state to render nodes');
        }

        return $renderContext;
    }

    /**
     * @param array<string, DocumentEntryNode|null> $documentEntries
     * @param RenderContext $renderContext
     * @return list<PageLinkNode>
     */
    public function getPageLinks(array $documentEntries, RenderContext $renderContext): array
    {
        $pagerList = [];
        foreach ($documentEntries as $rel => $documentEntry) {
            if ($documentEntry instanceof DocumentEntryNode) {
                $pagerList[] = new PageLinkNode(
                    $this->urlGenerator->generateCanonicalOutputUrl($renderContext, $documentEntry->getFile()),
                    $documentEntry->getTitle()->toString(),
                    $rel,
                );
            }
        }
        return $pagerList;
    }
}
