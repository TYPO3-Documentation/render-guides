<?php

declare(strict_types=1);

namespace T3Docs\Typo3DocsTheme\Changelog;

use phpDocumentor\Guides\Nodes\AnchorNode;
use phpDocumentor\Guides\Nodes\DocumentNode;
use phpDocumentor\Guides\Nodes\SectionNode;
use phpDocumentor\Guides\ReferenceResolvers\AnchorNormalizer;
use T3Docs\Typo3DocsTheme\Nodes\Metadata\IndexEntriesNode;

use function array_unique;
use function array_values;
use function basename;
use function preg_match;
use function strtolower;

/**
 * What a TYPO3 Core Changelog entry says about itself, read off the document.
 *
 * Everything here is in the entry already, just not where a reader of the
 * rendered page can see it: the release in the path, the kind of change and the
 * issue in the anchor, the tags in the ".. index::" directive. It is published
 * twice -- as front matter on the entry's own Markdown, and collected per
 * release into a JSON index beside the overview page -- so it is read in one
 * place, here, rather than once per output.
 *
 * Reads the document rather than the render context, so the same entry can be
 * described while rendering it and while walking all of them.
 */
final class ChangelogEntry
{
    /**
     * The Forge issue and the kind of change, both of which sit in the anchor.
     *
     * "feature-105638-1732034075" is a feature for issue 105638, the trailing
     * number being a timestamp that distinguishes the same issue backported to
     * several versions.
     *
     * Three shapes occur across the 3402 entries and all three are matched:
     * that one, the older "breaking-66431" with no timestamp, and
     * "changelog-Feature-91008-..." with the manual's name in front. Naming the
     * four kinds rather than accepting any word keeps the pattern from reading
     * a number out of an unrelated anchor.
     */
    private const ENTRY_REGEX = '#(?:^|-)(feature|breaking|deprecation|important)-(\d+)(?:-|$)#i';

    /** "Changelog/13.4.x/Feature-105638-…" -> "13.4.x". */
    private const VERSION_REGEX = '#(?:^|/)Changelog/(\d+\.\d+(?:\.x)?)/#';

    public function __construct(
        private readonly AnchorNormalizer $anchorNormalizer,
    ) {}

    /**
     * Everything known about one entry, or null when the document is not one.
     *
     * The Changelog holds index pages and a Howto beside its entries; they are
     * left out, the index pages because they carry no issue and the Howto
     * because it lives outside a version directory.
     *
     * @return array{
     *     path: string,
     *     title: string,
     *     anchor: string,
     *     type: string,
     *     issue: int,
     *     typo3-version: string,
     *     typo3-major: int,
     *     tags: list<string>,
     * }|null
     */
    public function describe(DocumentNode $document): array|null
    {
        $version = $this->version($document);
        $kind = $this->kind($document);
        if ($version === '' || $kind === null) {
            return null;
        }

        return [
            'path' => $document->getFilePath(),
            'title' => $document->getTitle()?->toString() ?? '',
            'anchor' => $this->anchor($document),
            'type' => $kind['type'],
            'issue' => $kind['issue'],
            'typo3-version' => $version,
            'typo3-major' => (int) $version,
            'tags' => $this->tags($document),
        ];
    }

    /**
     * The release the entry belongs to, or "" when it belongs to none.
     *
     * Not the version of the document: every entry of the Changelog claims
     * "main". The release is in the path, and the directory name is taken as it
     * stands, ".x" included -- "13.4.x" means a patch release of 13.4 rather
     * than 13.4.0, and normalising it away would drop that.
     *
     * Anything that is not a version directory under "Changelog/" yields "":
     * the Changelog's own "Howto" lives there too.
     */
    public function version(DocumentNode $document): string
    {
        if (preg_match(self::VERSION_REGEX, $document->getFilePath(), $matches) !== 1) {
            return '';
        }

        return $matches[1];
    }

    /**
     * The kind of change and its Forge issue, or null when the document is not
     * an entry.
     *
     * Both are read from the anchor rather than the title, because the anchor
     * is what permalinks resolve against.
     *
     * @return array{type: string, issue: int}|null
     */
    public function kind(DocumentNode $document): array|null
    {
        $matched = $this->match($this->anchor($document));
        if ($matched !== null) {
            return $matched;
        }

        // A handful of anchors name the subject instead of the issue --
        // "breaking-PageTsBackendLayoutDataProvider-1687440947". The file name
        // carries both in every one of those cases.
        return $this->match(basename($document->getFilePath()));
    }

    /**
     * The terms of the ".. index::" directive: "PHP-API", "FullyScanned",
     * "ext:lowlevel".
     *
     * Called tags rather than index because that is what they are here -- the
     * scanner status and the extension a change touches, not terms to look a
     * subject up by. They are the only categorisation an entry has, they never
     * appear in the rendered page, and the Changelog marks all 3402 of its
     * entries with them. See IndexEntriesDirective.
     *
     * @return list<string>
     */
    public function tags(DocumentNode $document): array
    {
        $terms = [];
        foreach ($document->getHeaderNodes() as $headerNode) {
            if (!$headerNode instanceof IndexEntriesNode) {
                continue;
            }

            foreach ($headerNode->getTerms() as $term) {
                $terms[] = $term;
            }
        }

        return array_values(array_unique($terms));
    }

    /** The label of the entry, which is what its permalink resolves against. */
    public function anchor(DocumentNode $document): string
    {
        foreach ($document->getChildren() as $child) {
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

        return '';
    }

    /** @return array{type: string, issue: int}|null */
    private function match(string $subject): array|null
    {
        if ($subject === '' || preg_match(self::ENTRY_REGEX, $subject, $matches) !== 1) {
            return null;
        }

        return ['type' => strtolower($matches[1]), 'issue' => (int) $matches[2]];
    }
}
