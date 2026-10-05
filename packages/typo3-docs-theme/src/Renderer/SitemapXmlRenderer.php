<?php

declare(strict_types=1);

namespace T3Docs\Typo3DocsTheme\Renderer;

use DateTimeInterface;
use phpDocumentor\Guides\Handlers\RenderCommand;
use phpDocumentor\Guides\Renderer\TypeRenderer;
use T3Docs\Typo3DocsTheme\Sitemap\ManualAddress;

use function htmlspecialchars;
use function sprintf;

use const ENT_XML1;

/**
 * "sitemap.xml" at the root of a manual: every page the table of contents
 * leads to, at its address on docs.typo3.org, and when it was rendered.
 *
 * Search engines and the crawlers of language models find the pages of a
 * manual by its links otherwise, every old version included. A sitemap is the
 * one list every crawler understands; the docs homepage lists the sitemaps of
 * the versions that matter in "sitemap-index.xml".
 *
 * Written only by a manual whose address is known. @see ManualAddress
 * Pages no table of contents leads to, such as the 404 page, are left out.
 */
final class SitemapXmlRenderer implements TypeRenderer
{
    public const FILE_NAME = 'sitemap.xml';

    public function __construct(
        private readonly ManualAddress $manualAddress,
    ) {}

    public function render(RenderCommand $renderCommand): void
    {
        $projectNode = $renderCommand->getProjectNode();
        $base = $this->manualAddress->of($projectNode->getVersion());
        if ($base === null) {
            return;
        }

        $lastmod = $projectNode->getLastRendered()->format(DateTimeInterface::ATOM);
        $urls = '';
        foreach ($projectNode->getAllDocumentEntries() as $entry) {
            if ($entry->isOrphan()) {
                continue;
            }
            $urls .= sprintf(
                "  <url>\n    <loc>%s</loc>\n    <lastmod>%s</lastmod>\n  </url>\n",
                htmlspecialchars($base . $entry->getFile() . '.html', ENT_XML1),
                $lastmod,
            );
        }

        $renderCommand->getDestination()->put(
            self::FILE_NAME,
            "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n"
            . "<urlset xmlns=\"http://www.sitemaps.org/schemas/sitemap/0.9\">\n"
            . $urls
            . "</urlset>\n",
        );
    }
}
