<?php

namespace T3Docs\Typo3DocsTheme\Renderer;

use phpDocumentor\Guides\Handlers\RenderCommand;
use phpDocumentor\Guides\Nodes\CompoundNode;
use phpDocumentor\Guides\Nodes\Node;
use phpDocumentor\Guides\RenderContext;
use phpDocumentor\Guides\Renderer\TypeRenderer;
use T3Docs\Typo3DocsTheme\ManualsIndex\ManualsIndex;
use T3Docs\Typo3DocsTheme\Nodes\MainMenuJsonNode;
use T3Docs\Typo3DocsTheme\Nodes\Metadata\TemplateNode;
use T3Docs\Typo3DocsTheme\Renderer\NodeRenderer\MainMenuJsonDocumentRenderer;
use T3Docs\VersionHandling\Typo3VersionMapping;

use function array_map;
use function htmlspecialchars;
use function in_array;
use function json_encode;
use function sprintf;

use const ENT_XML1;
use const JSON_PRETTY_PRINT;
use const JSON_UNESCAPED_SLASHES;
use const JSON_UNESCAPED_UNICODE;

/**
 * The main menu of docs.typo3.org, "mainmenu.json", and beside it the manuals
 * that menu lists, "manuals.json".
 *
 * Every manual publishes indexes of its own beside its pages, but only the
 * menu knows which manuals there are. "manuals.json" names each one by its
 * interlink shortcode, with the address of every version the menu offers:
 * where its "toc.json", "objects.inv.json" and the other indexes are.
 * @see ManualsIndex
 */
final class MainMenuJsonRenderer implements TypeRenderer
{
    public const MANUALS_FILE_NAME = 'manuals.json';

    /**
     * The sitemaps of those manuals, for the search engines: a sitemap index
     * named on its own, as the homepage is a manual with a sitemap.xml too.
     */
    public const SITEMAP_INDEX_FILE_NAME = 'sitemap-index.xml';

    /**
     * The indexes a manual publishes beside its pages, and which manuals do:
     * a consumer appends a name to the base of a version.
     */
    private const FILES = [
        'llms.txt' => 'every manual',
        'toc.json' => 'every manual',
        'objects.inv.json' => 'every manual',
        'classes.json' => 'every manual',
        'confvals.json' => 'a manual that documents options',
        'files.json' => 'a manual that defines files',
        'viewhelpers.json' => 'a manual that documents ViewHelpers',
    ];

    public function __construct(
        private readonly MainMenuJsonDocumentRenderer $renderer,
        private readonly ManualsIndex $manualsIndex,
    ) {}

    public function render(RenderCommand $renderCommand): void
    {
        $projectNode = $renderCommand->getProjectNode();

        $context = RenderContext::forProject(
            $projectNode,
            $renderCommand->getDocumentArray(),
            $renderCommand->getOrigin(),
            $renderCommand->getDestination(),
            $renderCommand->getDestinationPath(),
            'mainmenujson',
        )->withIterator($renderCommand->getDocumentIterator())
        ->withOutputFilePath('mainmenu.json');

        foreach ($renderCommand->getDocumentArray() as $key => $document) {
            $headerNodes = $document->getHeaderNodes();
            foreach ($headerNodes as $headerNode) {
                if ($headerNode instanceof TemplateNode && $headerNode->getValue() === 'mainmenu.json') {
                    $context = $context->withDocument($document);
                    $renderCommand->getDestination()->put(
                        'mainmenu.json',
                        $this->renderer->render(
                            $document,
                            $context,
                        ),
                    );

                    $menu = $this->menu($document);
                    if ($menu !== null) {
                        $manuals = $this->manualsIndex->of($menu, $context);
                        $renderCommand->getDestination()->put(
                            self::MANUALS_FILE_NAME,
                            (string) json_encode(
                                ['manuals' => $manuals, 'files' => self::FILES],
                                JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE,
                            ),
                        );
                        $renderCommand->getDestination()->put(
                            self::SITEMAP_INDEX_FILE_NAME,
                            $this->sitemapIndex($manuals),
                        );
                    }
                }
            }
        }
    }

    /**
     * The sitemaps of the versions a reader is sent to: the one in
     * development, the current LTS and the one before. Older versions stay
     * published, but are not where a search should lead.
     *
     * @param array<string, array{versions: list<array{version: string, base: string}>}> $manuals
     */
    private function sitemapIndex(array $manuals): string
    {
        $versions = array_map(
            static fn(Typo3VersionMapping $mapping): string => $mapping->getVersion(),
            [Typo3VersionMapping::Dev, Typo3VersionMapping::Stable, Typo3VersionMapping::OldStable],
        );

        $sitemaps = '';
        foreach ($manuals as $manual) {
            foreach ($manual['versions'] as $version) {
                if (!in_array($version['version'], $versions, true)) {
                    continue;
                }
                $sitemaps .= sprintf(
                    "  <sitemap>\n    <loc>%s</loc>\n  </sitemap>\n",
                    htmlspecialchars($version['base'] . SitemapXmlRenderer::FILE_NAME, ENT_XML1),
                );
            }
        }

        return "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n"
            . "<sitemapindex xmlns=\"http://www.sitemaps.org/schemas/sitemap/0.9\">\n"
            . $sitemaps
            . "</sitemapindex>\n";
    }

    private function menu(Node $node): ?MainMenuJsonNode
    {
        if ($node instanceof MainMenuJsonNode) {
            return $node;
        }
        if (!$node instanceof CompoundNode) {
            return null;
        }
        foreach ($node->getChildren() as $child) {
            $menu = $child instanceof Node ? $this->menu($child) : null;
            if ($menu !== null) {
                return $menu;
            }
        }

        return null;
    }
}
