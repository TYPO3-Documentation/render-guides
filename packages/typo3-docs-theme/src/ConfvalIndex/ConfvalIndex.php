<?php

declare(strict_types=1);

namespace T3Docs\Typo3DocsTheme\ConfvalIndex;

use phpDocumentor\Guides\Nodes\CompoundNode;
use phpDocumentor\Guides\Nodes\DocumentNode;
use phpDocumentor\Guides\Nodes\DocumentTree\DocumentEntryNode;
use phpDocumentor\Guides\Nodes\Inline\LinkInlineNode;
use phpDocumentor\Guides\Nodes\Node;
use phpDocumentor\Guides\Nodes\ParagraphNode;
use phpDocumentor\Guides\Nodes\ProjectNode;
use phpDocumentor\Guides\Nodes\SectionNode;
use phpDocumentor\Guides\ReferenceResolvers\AnchorNormalizer;
use phpDocumentor\Guides\RestructuredText\Nodes\ConfvalNode;
use T3Docs\Typo3DocsTheme\ConfvalVersions\ConfvalVersions;
use T3Docs\Typo3DocsTheme\Nodes\ConfvalMenuNode;
use T3Docs\Typo3DocsTheme\Search\SearchFacets;
use T3Docs\Typo3DocsTheme\Settings\Typo3DocsThemeSettings;

use function in_array;
use function is_string;
use function preg_replace;
use function trim;

/**
 * Every option a manual documents with ".. confval::", gathered from its
 * documents.
 *
 * An option is known by its anchor, which the project's permalink resolves.
 * Its name alone is no key: the TCA reference documents "itemsProcFunc" eight
 * times, once for each field type that has it. What tells them apart is where
 * each stands -- the titles of the pages and sections above it, and the option
 * it is nested in -- so every entry carries those.
 *
 * An option nested in another is that option's property. So is an option a
 * "confval-menu" lists whose ":parent:" names another, or one that names its
 * parent with ":parent:" itself: on a page that documents an object and its
 * properties side by side, which nesting would turn into one box without
 * headlines. Nesting comes first, then the option's own ":parent:", then the
 * menu's.
 *
 * The fields of the directive are passed on as written. Their names and
 * values are the manual's own, and differ between manuals. The search facet
 * of an option is not: it says what kind of option it is, from one list for
 * every manual. @see SearchFacets
 *
 * @phpstan-type Entry array{name: string, searchFacet: string, context: list<string>, parent?: string, type?: string, default?: string, required?: true, fields?: array<string, string>, versions?: list<array{kind: string, version: string, changelog?: string, url?: string}>, summary?: string, path: string}
 */
final class ConfvalIndex
{
    /** Fields the theme reads itself, which the index lists on their own or not at all. */
    private const SEARCH_FIELDS = ['searchFacet', 'searchKeywords', 'parent'];

    public function __construct(
        private readonly AnchorNormalizer $anchorNormalizer,
        private readonly SearchFacets $searchFacets,
        private readonly Typo3DocsThemeSettings $themeSettings,
        private readonly ConfvalVersions $confvalVersions,
    ) {}

    /**
     * @param iterable<DocumentNode> $documents
     * @return array<string, Entry> each option by its anchor, in the order of the documents
     */
    public function of(iterable $documents, ProjectNode $projectNode): array
    {
        $trails = [];
        $root = $projectNode->getRootDocumentEntry();
        foreach ($root->getChildren() as $child) {
            if ($child instanceof DocumentEntryNode) {
                $this->trails($child, [], $trails);
            }
        }

        $options = [];
        foreach ($documents as $document) {
            $path = $document->getFilePath();
            $menuParents = [];
            $this->menuParents($document, $menuParents);
            $this->walk($document, $trails[$path] ?? [], $path, null, $menuParents, $options);
        }

        return $options;
    }

    /**
     * The titles of the pages above each page, in the order the table of
     * contents nests them. The start page is left out: it is the manual.
     *
     * @param list<string> $above
     * @param array<string, list<string>> $trails
     */
    private function trails(DocumentEntryNode $entry, array $above, array &$trails): void
    {
        $trails[$entry->getFile()] ??= $above;
        $below = [...$above, $entry->getTitle()->toString()];
        foreach ($entry->getChildren() as $child) {
            if ($child instanceof DocumentEntryNode) {
                $this->trails($child, $below, $trails);
            }
        }
    }

    /**
     * The parent each option has from a "confval-menu" with ":parent:", by the
     * option's anchor. The first menu that lists an option counts.
     *
     * @param array<string, string> $menuParents
     */
    private function menuParents(Node $node, array &$menuParents): void
    {
        if ($node instanceof ConfvalMenuNode && $node->getParent() !== '') {
            $parent = $this->anchor($node->getParent());
            foreach ($node->getConfvals() as $confval) {
                $menuParents[$this->anchorNormalizer->reduceAnchor($confval->getAnchor())] ??= $parent;
            }
        }

        if (!$node instanceof CompoundNode) {
            return;
        }
        foreach ($node->getChildren() as $child) {
            if ($child instanceof Node) {
                $this->menuParents($child, $menuParents);
            }
        }
    }

    /** The anchor of the option with this name, as ":parent:" writes it: "cobj-text". */
    private function anchor(string $name): string
    {
        return $this->anchorNormalizer->reduceAnchor(ConfvalNode::LINK_PREFIX . $name);
    }

    /**
     * @param list<string> $context the titles above the node
     * @param array<string, string> $menuParents
     * @param array<string, Entry> $options
     */
    private function walk(Node $node, array $context, string $path, ?string $parent, array $menuParents, array &$options): void
    {
        if ($node instanceof SectionNode) {
            $context[] = $node->getTitle()->toString();
        }

        if ($node instanceof ConfvalNode) {
            if (!$node->isNoindex()) {
                $anchor = $this->anchorNormalizer->reduceAnchor($node->getAnchor());
                $ownParent = $this->text($node->getAdditionalOptions()['parent'] ?? null);
                $options[$anchor] = $this->entry(
                    $node,
                    $context,
                    $path,
                    $parent ?? ($ownParent !== '' ? $this->anchor($ownParent) : null) ?? $menuParents[$anchor] ?? null,
                );
                $parent = $anchor;
            }
            // What is nested in an option belongs to it, not to a section.
            $context = [...$context, $node->getPlainContent()];
        }

        if (!$node instanceof CompoundNode) {
            return;
        }

        foreach ($node->getChildren() as $child) {
            if ($child instanceof Node) {
                $this->walk($child, $context, $path, $parent, $menuParents, $options);
            }
        }
    }

    /**
     * @param list<string> $context
     * @return Entry
     */
    private function entry(ConfvalNode $node, array $context, string $path, ?string $parent): array
    {
        // Left out rather than written empty: most options have no default
        // and are not required, and the TCA reference alone has 757 of them.
        $entry = [
            'name' => $node->getPlainContent(),
            'searchFacet' => $this->searchFacet($node),
            'context' => $context,
        ];
        if ($parent !== null) {
            $entry['parent'] = $parent;
        }

        $type = $this->text($node->getType());
        if ($type !== '') {
            $entry['type'] = $type;
        }
        $default = $this->text($node->getDefault());
        if ($default !== '') {
            $entry['default'] = $default;
        }
        if ($node->isRequired()) {
            $entry['required'] = true;
        }

        $fields = [];
        foreach ($node->getAdditionalOptions() as $name => $value) {
            if (in_array($name, self::SEARCH_FIELDS, true) || ConfvalVersions::isVersionOption($name)) {
                continue;
            }
            $text = $this->text($value);
            if ($text !== '') {
                $fields[$name] = $text;
            }
        }
        if ($fields !== []) {
            $entry['fields'] = $fields;
        }

        $versions = $this->versions($node);
        if ($versions !== []) {
            $entry['versions'] = $versions;
        }

        $summary = $this->summary($node);
        if ($summary !== '') {
            $entry['summary'] = $summary;
        }

        $entry['path'] = $path;

        return $entry;
    }

    /**
     * When the option was added, changed, deprecated or removed, with the
     * changelog entry that says more, as the confval states it.
     *
     * @return list<array{kind: string, version: string, changelog?: string, url?: string}>
     */
    private function versions(ConfvalNode $node): array
    {
        $versions = [];
        foreach ($this->confvalVersions->of($node) as $version) {
            $entry = ['kind' => $version['kind'], 'version' => $version['version']];
            if ($version['changelog'] !== '') {
                $entry['changelog'] = $version['changelog'];
            }
            // Resolved when the page was rendered to HTML, before this file.
            $url = $version['reference'] === null ? '' : $version['reference']->getUrl();
            if ($url !== '') {
                $entry['url'] = $url;
            }
            $versions[] = $entry;
        }

        return $versions;
    }

    /**
     * What kind of option it is, as the search of docs.typo3.org files it:
     * its own ":searchFacet:", or the manual's "confval-default".
     */
    private function searchFacet(ConfvalNode $node): string
    {
        $facet = $this->text($node->getAdditionalOptions()['searchFacet'] ?? null);

        return $this->searchFacets->of($facet !== '' ? $facet : $this->themeSettings->getSettings('confval_default'));
    }

    /**
     * The first paragraph of the option's own description, as plain text:
     * enough to choose between options of the same name without opening the
     * page. A paragraph of an option nested in it is that option's.
     */
    private function summary(ConfvalNode $node): string
    {
        foreach ($node->getChildren() as $child) {
            if ($child instanceof ParagraphNode) {
                return $this->text($child);
            }
        }

        return '';
    }

    private function text(?Node $node): string
    {
        if ($node === null) {
            return '';
        }

        if ($node instanceof CompoundNode) {
            $text = $this->plain($node);
        } else {
            $value = $node->getValue();
            $text = is_string($value) ? $value : '';
        }

        return trim((string) preg_replace('/\s+/', ' ', $text));
    }

    /**
     * The text of a node and what it contains. A reference without a link
     * text has none until it is rendered, so its target stands in for it.
     *
     * @param CompoundNode<Node> $node
     */
    private function plain(CompoundNode $node): string
    {
        $text = '';
        foreach ($node->getChildren() as $child) {
            if (!$child instanceof Node) {
                continue;
            }
            if (!$child instanceof CompoundNode) {
                $value = $child->getValue();
                $text .= is_string($value) ? $value : '';
                continue;
            }
            $inner = $this->plain($child);
            $text .= $inner === '' && $child instanceof LinkInlineNode ? $child->getTargetReference() : $inner;
        }

        return $text;
    }
}
