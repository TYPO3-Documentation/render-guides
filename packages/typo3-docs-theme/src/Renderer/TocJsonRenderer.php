<?php

declare(strict_types=1);

namespace T3Docs\Typo3DocsTheme\Renderer;

use phpDocumentor\Guides\Handlers\RenderCommand;
use phpDocumentor\Guides\Nodes\AnchorNode;
use phpDocumentor\Guides\Nodes\DocumentNode;
use phpDocumentor\Guides\Nodes\DocumentTree\DocumentEntryNode;
use phpDocumentor\Guides\Nodes\ProjectNode;
use phpDocumentor\Guides\Nodes\SectionNode;
use phpDocumentor\Guides\ReferenceResolvers\AnchorNormalizer;
use phpDocumentor\Guides\Renderer\TypeRenderer;
use T3Docs\Typo3DocsTheme\Permalinks\Permalinks;

use function json_encode;

use const JSON_PRETTY_PRINT;
use const JSON_UNESCAPED_SLASHES;
use const JSON_UNESCAPED_UNICODE;

/**
 * The table of contents of a manual as one JSON file.
 *
 * "toc.json" at the root of every manual: what pages there are, in the order
 * the table of contents puts them and nested the way it nests them, each with
 * its title and the anchor its permalink is built from.
 *
 * A tool that wants to read a manual has to learn its shape first, and nothing
 * published so far says it. "objects.inv.json" comes closest, but it is an
 * index rather than a table of contents: a flat map without order or nesting,
 * which repeats the project title and version in every one of its entries and
 * knows only ".html" addresses. This file states those once, at the top, and
 * leaves each page with what belongs to the page alone.
 *
 * Paths carry no extension because the same page exists twice, as ".html" and
 * as ".md", and a reader wants to choose. They are relative to this file, which
 * sits where the manual starts, so they work in a local render and under
 * docs.typo3.org alike.
 */
final class TocJsonRenderer implements TypeRenderer
{
    public function __construct(
        private readonly AnchorNormalizer $anchorNormalizer,
        private readonly Permalinks $permalinks,
    ) {}

    public function render(RenderCommand $renderCommand): void
    {
        $projectNode = $renderCommand->getProjectNode();

        $documents = [];
        foreach ($renderCommand->getDocumentArray() as $document) {
            $documents[$document->getFilePath()] = $document;
        }

        $toc = [
            'project' => [
                'title' => $projectNode->getTitle() ?? '',
                'version' => $this->permalinks->normalizeVersion($projectNode->getVersion()),
                // The permalink of every page at once: a consumer puts a page's
                // anchor where the placeholder is. Empty for a manual that
                // declares no interlink shortcode, which has no permalinks.
                'permalink' => $this->permalinks->pattern($projectNode->getVersion()),
            ],
            'pages' => [$this->describe($projectNode->getRootDocumentEntry(), $documents)],
        ];

        foreach ($this->orphans($projectNode) as $orphan) {
            // Not reachable through any toctree, so the walk above never saw
            // it. Listed all the same: the file is published, and a table of
            // contents that silently drops published pages is worse than one
            // that says where they stand.
            $toc['pages'][] = ['orphan' => true, ...$this->describe($orphan, $documents)];
        }

        $renderCommand->getDestination()->put(
            'toc.json',
            (string) json_encode($toc, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
        );
    }

    /**
     * One page and, nested below it, the pages its table of contents leads to.
     *
     * @param array<string, DocumentNode> $documents
     * @return array<string, mixed>
     */
    private function describe(DocumentEntryNode $entry, array $documents): array
    {
        $page = [
            'path' => $entry->getFile(),
            'title' => $entry->getTitle()->toString(),
            'anchor' => $this->anchor($entry, $documents),
        ];

        $children = [];
        foreach ($entry->getChildren() as $child) {
            if ($child instanceof DocumentEntryNode) {
                $children[] = $this->describe($child, $documents);
            }
        }

        // Left out rather than written as an empty list: most pages of a large
        // manual are leaves, and the Changelog alone would spend 50 KB saying
        // so 3875 times.
        if ($children !== []) {
            $page['pages'] = $children;
        }

        return $page;
    }

    /**
     * The label the page's permalink is built from, or "" when it has none.
     *
     * The explicit label of the first section, the way a ":ref:" to the page
     * names it, and otherwise the id derived from its title -- which the
     * inventory registers as well, so a permalink built on it resolves.
     *
     * @param array<string, DocumentNode> $documents
     */
    private function anchor(DocumentEntryNode $entry, array $documents): string
    {
        foreach ($documents[$entry->getFile()]?->getChildren() ?? [] as $child) {
            if (!$child instanceof SectionNode) {
                continue;
            }

            foreach ($child->getChildren() as $sectionChild) {
                if ($sectionChild instanceof AnchorNode) {
                    return $this->anchorNormalizer->reduceAnchor($sectionChild->toString());
                }
            }

            break;
        }

        $id = $entry->getTitle()->getId();

        return $id === '' ? '' : $this->anchorNormalizer->reduceAnchor($id);
    }

    /**
     * Every page no table of contents leads to, in the order the project knows
     * them.
     *
     * @return list<DocumentEntryNode>
     */
    private function orphans(ProjectNode $projectNode): array
    {
        $reached = [];
        $this->collect($projectNode->getRootDocumentEntry(), $reached);

        $orphans = [];
        foreach ($projectNode->getAllDocumentEntries() as $entry) {
            if (!isset($reached[$entry->getFile()])) {
                $orphans[] = $entry;
            }
        }

        return $orphans;
    }

    /** @param array<string, true> $reached */
    private function collect(DocumentEntryNode $entry, array &$reached): void
    {
        if (isset($reached[$entry->getFile()])) {
            return;
        }

        $reached[$entry->getFile()] = true;
        foreach ($entry->getChildren() as $child) {
            if ($child instanceof DocumentEntryNode) {
                $this->collect($child, $reached);
            }
        }
    }
}
