<?php

declare(strict_types=1);

namespace T3Docs\Typo3DocsTheme\Renderer;

use phpDocumentor\Guides\Handlers\RenderCommand;
use phpDocumentor\Guides\Nodes\DocumentTree\DocumentEntryNode;
use phpDocumentor\Guides\Nodes\ProjectNode;
use phpDocumentor\Guides\Renderer\TypeRenderer;
use T3Docs\Typo3DocsTheme\Permalinks\Permalinks;
use T3Docs\VersionHandling\DefaultInventories;
use T3Docs\VersionHandling\Typo3VersionMapping;

use function addcslashes;
use function array_unshift;
use function ctype_digit;
use function implode;
use function sprintf;
use function str_repeat;
use function trim;

/**
 * "llms.txt" at the root of a manual: where a language model starts reading it.
 *
 * A manual publishes machine-readable indexes beside its pages, and its pages
 * as Markdown, but a client that does not know the names of those files finds
 * none of them. This file names them, in the Markdown the llms.txt convention
 * asks for: the manual, the indexes it was rendered with, and every page its
 * table of contents leads to, nested as there, in Markdown where the manual
 * was rendered to Markdown.
 *
 * Only the indexes this render wrote are listed, so it runs after them: a
 * manual without options names no "confvals.json". The pages no table of
 * contents leads to are named too: the official manuals keep there what
 * they removed.
 *
 * The docs homepage writes none: its llms.txt, for all of docs.typo3.org, is
 * written by hand.
 *
 * @see https://llmstxt.org/
 */
final class LlmsTxtRenderer implements TypeRenderer
{
    public const FILE_NAME = 'llms.txt';

    /**
     * How deep the Core Changelog lists its pages: down to each version, not
     * to the thousands of entries, which its Changelog-<major>.json lists
     * with more to filter by. @see ChangelogJsonRenderer
     */
    private const CHANGELOG_DEPTH = 2;

    /**
     * The page a manual keeps what it removed on, by TYPO3 version, with the
     * anchors that led there before. Listed first among the other pages.
     */
    private const REMOVED_CONTENT = '404';

    /** The title guides gives a page that has none. */
    private const UNTITLED = '<Unknown>';

    /** The indexes a manual can be rendered with, and what each holds. */
    private const INDEXES = [
        'toc.json' => 'Table of contents: every page in order, with its title and permalink anchor',
        'classes.json' => 'Every PHP class the manual speaks of, and where',
        'confvals.json' => 'Every option the manual documents, with its type, default and context',
        'viewhelpers.json' => 'Every ViewHelper the manual documents, with its arguments',
        'files.json' => 'Every file the manual defines, with its paths',
        'objects.inv.json' => 'Every link target, as the interlinks of other manuals use them',
        'sitemap.xml' => 'Every page, for search engines',
    ];

    public function __construct(
        private readonly Permalinks $permalinks,
        private readonly PageFiles $pageFiles,
    ) {}

    public function render(RenderCommand $renderCommand): void
    {
        if ($this->permalinks->shortcode() === DefaultInventories::t3docs->value) {
            return;
        }

        $projectNode = $renderCommand->getProjectNode();
        $destination = $renderCommand->getDestination();

        $title = trim($projectNode->getTitle() ?? '');
        $version = $this->permalinks->normalizeVersion($projectNode->getVersion());
        $text = sprintf("# %s\n\n", $title !== '' ? $title : 'Documentation');

        $about = [];
        if ($version !== '') {
            $about[] = sprintf('Version %s of this manual.', $version);
        }
        if (isset($this->pageFiles->of('')['md'])) {
            $about[] = 'Every page is also available as Markdown: the same path with .md instead of .html.';
        }
        $pattern = $this->permalinks->pattern($projectNode->getVersion());
        if ($pattern !== '') {
            $about[] = sprintf('The permalink of an anchor is %s.', $pattern);
        }
        if ($about !== []) {
            $text .= '> ' . implode("\n> ", $about) . "\n\n";
        }

        $indexes = '';
        foreach (self::INDEXES as $file => $description) {
            if ($destination->has($file)) {
                $indexes .= sprintf("- [%s](%s): %s\n", $file, $file, $description);
            }
        }
        foreach (Typo3VersionMapping::cases() as $mapping) {
            // The Core Changelog, one file per major version.
            $file = sprintf('Changelog-%s.json', $mapping->value);
            if (ctype_digit($mapping->value) && $destination->has($file)) {
                $indexes .= sprintf(
                    "- [%s](%s): Every entry of TYPO3 %s with its type, issue, tags, version and the classes it names, and the link to its Markdown page\n",
                    $file,
                    $file,
                    $mapping->value,
                );
            }
        }
        if ($indexes !== '') {
            $text .= "## Indexes\n\n" . $indexes . "\n";
        }

        $isChangelog = $this->permalinks->shortcode() === DefaultInventories::changelog->value;
        $text .= "## Pages\n\n";
        if ($isChangelog) {
            $text .= "To find entries, filter Changelog-<major>.json; each entry links its page.\n"
                . "The pages of each version list its entries by title.\n\n";
        }
        $text .= $this->pages($projectNode->getRootDocumentEntry(), 0, $isChangelog ? self::CHANGELOG_DEPTH : null);

        $text .= $this->otherPages($projectNode);

        $destination->put(self::FILE_NAME, $text);
    }

    /**
     * The pages no table of contents leads to. The official manuals keep
     * there what they removed, so that old anchors and permalinks still lead
     * somewhere; a page without a title is left out, as nothing says what it
     * is.
     */
    private function otherPages(ProjectNode $projectNode): string
    {
        // What the table of contents reaches, all of it, as toc.json counts
        // it: a page may say ":orphan:" and still be linked from a toctree,
        // as the version overviews of the Core Changelog are.
        $reached = [];
        $this->reach($projectNode->getRootDocumentEntry(), $reached);

        $pages = [];
        foreach ($projectNode->getAllDocumentEntries() as $entry) {
            $title = trim($entry->getTitle()->toString());
            if (isset($reached[$entry->getFile()]) || $title === '' || $title === self::UNTITLED) {
                continue;
            }
            $files = $this->pageFiles->of($entry->getFile());
            $line = sprintf("- [%s](%s)\n", addcslashes($title, '[]'), $files['md'] ?? $files['html'] ?? $entry->getFile());
            if ($entry->getFile() === self::REMOVED_CONTENT) {
                array_unshift($pages, $line);
            } else {
                $pages[] = $line;
            }
        }
        if ($pages === []) {
            return '';
        }

        return "\n## Other pages\n\n"
            . "No table of contents leads to these pages. Most keep what was removed from this manual,\n"
            . "and old anchors and permalinks lead there.\n\n"
            . implode('', $pages);
    }

    /** @param array<string, true> $reached */
    private function reach(DocumentEntryNode $entry, array &$reached): void
    {
        $reached[$entry->getFile()] = true;
        foreach ($entry->getChildren() as $child) {
            if ($child instanceof DocumentEntryNode && !isset($reached[$child->getFile()])) {
                $this->reach($child, $reached);
            }
        }
    }

    /**
     * A page and, nested below it, the pages its table of contents leads to,
     * as deep as $maxDepth where there is one.
     */
    private function pages(DocumentEntryNode $entry, int $depth, ?int $maxDepth): string
    {
        $files = $this->pageFiles->of($entry->getFile());
        $file = $files['md'] ?? $files['html'] ?? $entry->getFile();
        $text = sprintf(
            "%s- [%s](%s)\n",
            str_repeat('  ', $depth),
            addcslashes(trim($entry->getTitle()->toString()), '[]'),
            $file,
        );

        if ($maxDepth !== null && $depth >= $maxDepth) {
            return $text;
        }
        foreach ($entry->getChildren() as $child) {
            if ($child instanceof DocumentEntryNode) {
                $text .= $this->pages($child, $depth + 1, $maxDepth);
            }
        }

        return $text;
    }
}
