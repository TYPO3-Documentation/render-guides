<?php

declare(strict_types=1);

namespace T3Docs\Typo3DocsTheme\Twig;

use League\Flysystem\Exception;
use LogicException;
use phpDocumentor\Guides\Nodes\AnchorNode;
use phpDocumentor\Guides\Nodes\DocumentTree\DocumentEntryNode;
use phpDocumentor\Guides\Nodes\LinkTargetNode;
use phpDocumentor\Guides\Nodes\Metadata\NoSearchNode;
use phpDocumentor\Guides\Nodes\Metadata\OrphanNode;
use phpDocumentor\Guides\Nodes\Node;
use phpDocumentor\Guides\Nodes\SectionNode;
use phpDocumentor\Guides\ReferenceResolvers\DocumentNameResolverInterface;
use phpDocumentor\Guides\RenderContext;
use phpDocumentor\Guides\Renderer\UrlGenerator\UrlGeneratorInterface;
use phpDocumentor\Guides\RestructuredText\Nodes\ConfvalNode;
use Psr\Log\LoggerInterface;
use RuntimeException;
use T3Docs\GuidesPhpDomain\Nodes\PhpComponentNode;
use T3Docs\GuidesPhpDomain\Nodes\PhpMemberNode;
use T3Docs\Typo3DocsTheme\Inventory\Typo3VersionService;
use T3Docs\Typo3DocsTheme\Nodes\Metadata\EditOnGitHubNode;
use T3Docs\Typo3DocsTheme\Nodes\Metadata\TemplateNode;
use T3Docs\Typo3DocsTheme\Nodes\PageLinkNode;
use T3Docs\Typo3DocsTheme\Settings\Typo3DocsThemeSettings;
use T3Docs\VersionHandling\DefaultInventories;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

final class TwigExtension extends AbstractExtension
{
    private string $typo3AzureEdgeURI = '';

    public function __construct(
        private readonly LoggerInterface               $logger,
        private readonly UrlGeneratorInterface         $urlGenerator,
        private readonly Typo3DocsThemeSettings        $themeSettings,
        private readonly DocumentNameResolverInterface $documentNameResolver,
        private readonly Typo3VersionService           $typo3VersionService,
    ) {
        if (strlen((string)getenv('GITHUB_ACTIONS')) > 0 && strlen((string)getenv('TYPO3AZUREEDGEURIVERSION')) > 0 && !isset($_ENV['CI_PHPUNIT'])) {
            // CI gets special treatment, then we use a fixed URI for assets.
            // The environment variable 'TYPO3AZUREEDGEURIVERSION' is set during
            // the creation of our Docker image, and holds the last pushed version
            // number. This version number will then only be utilized in CI GitHub Action
            // executions, and sets links to resources/assets to a public CDN.
            // Outside CI (and for local development) all Assets are linked locally.
            // This is prevented when being run within PHPUnit.
            $this->typo3AzureEdgeURI = 'https://typo3.azureedge.net/typo3documentation/theme/typo3-docs-theme/' . getenv('TYPO3AZUREEDGEURIVERSION') . '/';
        }
    }

    /** @return TwigFunction[] */
    public function getFunctions(): array
    {
        return [
            new TwigFunction('renderPlainText', $this->renderPlainText(...), ['needs_context' => false]),
            new TwigFunction('getAnchorIdOfSection', $this->getAnchorIdOfSection(...), ['needs_context' => true]),
            new TwigFunction('getEditOnGitHubLink', $this->getEditOnGitHubLink(...), ['needs_context' => true]),
            new TwigFunction('getEditOnGitHubLinkFromPath', $this->getEditOnGitHubLinkFromPath(...), ['needs_context' => true]),
            new TwigFunction('getCurrentFilename', $this->getCurrentFilename(...), ['needs_context' => true]),
            new TwigFunction('getRelativePath', $this->getRelativePath(...), ['needs_context' => true]),
            new TwigFunction('getPagerLinks', $this->getPagerLinks(...), ['is_safe' => ['html'], 'needs_context' => true]),
            new TwigFunction('getPrevNextLinks', $this->getPrevNextLinks(...), ['is_safe' => ['html'], 'needs_context' => true]),
            new TwigFunction('getSettings', $this->getSettings(...), ['is_safe' => ['html'], 'needs_context' => true]),
            new TwigFunction('getTYPO3Version', $this->getTYPO3Version(...), ['is_safe' => ['html'], 'needs_context' => false]),
            new TwigFunction('isNoSearch', $this->isNoSearch(...), ['is_safe' => ['html'], 'needs_context' => true]),
            new TwigFunction('copyDownload', $this->copyDownload(...), ['is_safe' => ['html'], 'needs_context' => true]),
            new TwigFunction('getStandardInventories', $this->getStandardInventories(...), ['is_safe' => ['html'], 'needs_context' => true]),
            new TwigFunction('getRstCodeForLink', $this->getRstCodeForLink(...), ['is_safe' => [], 'needs_context' => true]),
            new TwigFunction('isRenderedForDeployment', $this->isRenderedForDeployment(...)),
        ];
    }

    public function renderPlainText(mixed $value): string
    {
        if ($value === null) {
            return '';
        }
        if (is_scalar($value)) {
            return (string)$value;
        }
        if (is_array($value)) {
            $string = '';
            foreach ($value as $child) {
                $string .= $this->renderPlainText($child);
            }
            return $string;
        }
        if ($value instanceof LinkTargetNode) {
            return $this->renderPlainText($value->getLinkText());
        }
        if ($value instanceof Node) {
            return $this->renderPlainText($value->getValue());
        }
        if (is_object($value)) {
            throw new \Exception('Cannot render object ' . get_class($value) . ' as plaintext.');
        } else {
            throw new \Exception('Cannot render type ' . gettype($value) . ' as plaintext.');
        }
    }

    /**
     * @param array{env: RenderContext} $context
     */
    public function isNoSearch(array $context): bool
    {
        $renderContext = $this->getRenderContext($context);
        try {
            if ($renderContext->getCurrentDocumentEntry() === null) {
                return false;
            }
            $document = $renderContext->getDocumentNodeForEntry($renderContext->getCurrentDocumentEntry());
        } catch (\Exception) {
            return false;
        }
        $headerNodes = $document->getHeaderNodes();
        foreach ($headerNodes as $headerNode) {
            if ($headerNode instanceof NoSearchNode) {
                return true;
            }
            // Disable searching on orphans
            if ($headerNode instanceof OrphanNode) {
                return true;
            }
            // Disable searching on sitemaps
            if ($headerNode instanceof TemplateNode && $headerNode->getValue() === 'sitemap.html') {
                return true;
            }
            // Disable searching on changelog indexes
            if ($headerNode instanceof TemplateNode && $headerNode->getValue() === 'changelogOverview.html') {
                return true;
            }
        }
        return false;
    }

    /**
     * @param array{env: RenderContext} $context
     */
    public function getRstCodeForLink(array $context, LinkTargetNode $linkTargetNode): string
    {
        $interlink = $this->themeSettings->getSettings('interlink_shortcode') !== '' ? $this->themeSettings->getSettings('interlink_shortcode') : 'somemanual';
        if ($linkTargetNode->getLinkType() === ConfvalNode::LINK_TYPE) {
            return sprintf(
                ':confval:`%s <%s:%s>`',
                $linkTargetNode->getLinkText(),
                $interlink,
                $linkTargetNode->getId()
            );
        }
        if ($linkTargetNode instanceof PhpComponentNode) {
            return sprintf(
                ':%s:`%s:%s`',
                $linkTargetNode->getLinkType(),
                $interlink,
                $linkTargetNode->getName()->toString()
            );
        }
        if ($linkTargetNode instanceof PhpMemberNode) {
            return sprintf(
                ':%s:`%s:%s`',
                $linkTargetNode->getLinkType(),
                $interlink,
                $linkTargetNode->getFullyQualifiedName()
            );
        }
        return '';
    }

    /**
     * @param array{env: RenderContext} $context
     */
    public function getAnchorIdOfSection(array $context, SectionNode $sectionNode): string
    {
        foreach ($sectionNode->getChildren() as $childNode) {
            if ($childNode instanceof AnchorNode) {
                return $childNode->toString();
            }
        }
        return '';
    }
    /**
     * @param array{env: RenderContext} $context
     */
    public function getEditOnGitHubLinkFromPath(array $context, ?string $path): string
    {
        if (($path ?? '') === '') {
            return '';
        }
        $githubButton = $this->themeSettings->getSettings('edit_on_github');
        if ($githubButton === '') {
            return '';
        }
        $githubBranch = $this->themeSettings->getSettings('edit_on_github_branch', 'main');
        $currentFileName = $this->getCurrentFilename($context);
        if ($currentFileName === '') {
            return '';
        }
        $githubDirectory = trim($this->themeSettings->getSettings('edit_on_github_directory', 'Documentation'), '/');
        return sprintf("https://github.com/%s/edit/%s/%s%s", $githubButton, $githubBranch, $githubDirectory, $path);
    }

    /**
     * @param array{env: RenderContext} $context
     */
    public function getEditOnGitHubLink(array $context): string
    {
        $renderContext = $this->getRenderContext($context);
        $githubButton = $this->themeSettings->getSettings('edit_on_github');
        if ($githubButton === '') {
            return '';
        }
        $githubBranch = $this->themeSettings->getSettings('edit_on_github_branch', 'main');
        $currentFileName = $this->getCurrentFilename($context);
        if ($currentFileName === '') {
            return '';
        }
        $gitHubPerPageLink = $this->getEditOnGitHubLinkPerPage($renderContext);

        $githubDirectory = trim($this->themeSettings->getSettings('edit_on_github_directory', 'Documentation'), '/');
        return $gitHubPerPageLink ?? sprintf("https://github.com/%s/edit/%s/%s/%s.rst", $githubButton, $githubBranch, $githubDirectory, $currentFileName);
    }

    private function getEditOnGitHubLinkPerPage(RenderContext $renderContext): string|null
    {
        try {
            if ($renderContext->getCurrentDocumentEntry() === null) {
                return null;
            }
            $document = $renderContext->getDocumentNodeForEntry($renderContext->getCurrentDocumentEntry());
        } catch (\Exception) {
            return null;
        }
        $headerNodes = $document->getHeaderNodes();
        foreach ($headerNodes as $headerNode) {
            if ($headerNode instanceof EditOnGitHubNode) {
                return $headerNode->toString();
            }
        }
        return null;
    }

    /**
     * @param array{env: RenderContext} $context
     * @return list<string>
     */
    public function getStandardInventories(array $context): array
    {
        $outputArray = array_map(fn($value) => $value->value, DefaultInventories::cases());
        sort($outputArray, SORT_STRING);

        return $outputArray;
    }

    /**
     * @param array{env: RenderContext} $context
     */
    public function getCurrentFilename(array $context): string
    {
        $renderContext = $this->getRenderContext($context);
        try {
            return $renderContext->getCurrentFileName();
        } catch (\Exception) {
            return '';
        }
    }

    /**
     * @param array{env: RenderContext} $context
     */
    public function getRelativePath(array $context, string $path): string
    {
        $renderContext = $this->getRenderContext($context);
        if ($this->typo3AzureEdgeURI !== '') {
            // CI (GitHub Actions) gets special treatment, then we use a fixed URI for assets.
            // TODO: Fixate the "_resources" string as a class/config constant, not hardcoded
            // (see packages/typo3-docs-theme/src/EventListeners/CopyResources.php)
            return str_replace('/_resources/', '/', $this->typo3AzureEdgeURI . $path);
        } else {
            return $this->urlGenerator->generateInternalUrl($context['env'] ?? null, $path);
        }
    }

    /**
     * @param array{env: RenderContext} $context
     * @return string
     */
    public function copyDownload(
        array  $context,
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
        string             $sourcePath,
        string             $targetPath
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
        } catch (LogicException|Exception $e) {
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

    public function getTYPO3Version(): string
    {
        return $this->typo3VersionService->getPreferredVersion();
    }

    /**
     * @param array{env: RenderContext} $context
     * @return list<PageLinkNode>
     */
    public function getPagerLinks(array $context): array
    {
        $renderContext = $this->getRenderContext($context);
        try {
            $documentEntries = [
                'prev' => $this->getPrevDocumentEntry($renderContext),
                'next' => $this->getNextDocumentEntry($renderContext),
                'top' => $this->getTopDocumentEntry($renderContext),
            ];
            return $this->getPageLinks($documentEntries, $renderContext);
        } catch (\Exception) {
            $documentEntries = [
                'top' => $this->getTopDocumentEntry($renderContext),
            ];
            return $this->getPageLinks($documentEntries, $renderContext);
        }
    }

    /**
     * @param array{env: RenderContext} $context
     * @return list<PageLinkNode>
     */
    public function getPrevNextLinks(array $context): array
    {
        $renderContext = $this->getRenderContext($context);
        try {
            $documentEntries = [
                'prev' => $this->getPrevDocumentEntry($renderContext),
                'next' => $this->getNextDocumentEntry($renderContext),
            ];
            return $this->getPageLinks($documentEntries, $renderContext);
        } catch (\Exception) {
            return [];
        }
    }

    private function getNextDocumentEntry(RenderContext $renderContext): DocumentEntryNode|null
    {
        return $renderContext->getIterator()->nextNode()?->getDocumentEntry();
    }

    private function getPrevDocumentEntry(RenderContext $renderContext): DocumentEntryNode|null
    {
        return $renderContext->getIterator()->previousNode()?->getDocumentEntry();
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

    public function isRenderedForDeployment(): bool
    {
        if ($this->typo3AzureEdgeURI !== '') {
            return true;
        }

        return false;
    }
}
