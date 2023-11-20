<?php

declare(strict_types=1);

namespace T3Docs\Typo3DocsTheme\Twig;

use phpDocumentor\Guides\Nodes\DocumentTree\DocumentEntryNode;
use phpDocumentor\Guides\RenderContext;
use phpDocumentor\Guides\Renderer\UrlGenerator\UrlGeneratorInterface;
use RuntimeException;
use T3Docs\Typo3DocsTheme\Nodes\PageLinkNode;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

final class TwigExtension extends AbstractExtension
{
    public function __construct(
        private readonly UrlGeneratorInterface $urlGenerator,
    ) {}

    /** @return TwigFunction[] */
    public function getFunctions(): array
    {
        return [

            new TwigFunction('getPagerLinks', $this->getPagerLinks(...), ['is_safe' => ['html'], 'needs_context' => true]),
            new TwigFunction('getPrevNextLinks', $this->getPrevNextLinks(...), ['is_safe' => ['html'], 'needs_context' => true]),
        ];
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
