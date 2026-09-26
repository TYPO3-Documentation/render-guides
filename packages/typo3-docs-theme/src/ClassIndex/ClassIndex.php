<?php

declare(strict_types=1);

namespace T3Docs\Typo3DocsTheme\ClassIndex;

use phpDocumentor\Guides\Nodes\AnchorNode;
use phpDocumentor\Guides\Nodes\CodeNode;
use phpDocumentor\Guides\Nodes\CompoundNode;
use phpDocumentor\Guides\Nodes\DocumentNode;
use phpDocumentor\Guides\Nodes\Node;
use phpDocumentor\Guides\Nodes\SectionNode;
use phpDocumentor\Guides\ReferenceResolvers\AnchorNormalizer;
use T3Docs\Typo3DocsTheme\Api\Typo3ApiService;
use T3Docs\Typo3DocsTheme\Changelog\ChangelogEntry;
use T3Docs\GuidesPhpDomain\Nodes\PhpComponentNode;
use T3Docs\Typo3DocsTheme\Nodes\Inline\CodeInlineNode;
use T3Docs\Typo3DocsTheme\Settings\Typo3DocsThemeSettings;

use function array_values;
use function explode;
use function implode;
use function in_array;
use function ksort;
use function ltrim;
use function sort;
use function str_starts_with;
use function trim;
use function usort;

/**
 * Where each PHP class is spoken of, gathered from the documents of a manual.
 *
 * Three things count as speaking of a class: the ":php:" and ":php-short:"
 * roles, a "use" statement in a PHP example, and the directive with which a
 * manual documents the class itself. The last is a definition rather than a
 * mention, and says so.
 *
 * Each place names the page and the nearest label above it, which the
 * project's permalink resolves, and the section it stands in where that
 * section has no label of its own -- the "Migration" of a Changelog entry, say.
 * What a section names several members of is one place listing them.
 *
 * A class the TYPO3 API knows carries what it is -- class, interface, trait or
 * enum. A name below "TYPO3" without one is a namespace, a typo or an
 * invention, and none of those can be told from the writing alone: a "use"
 * statement imports a namespace and a class alike.
 *
 * @phpstan-type Position array{path: string, typo3-version?: string, anchor: string, section?: string}
 * @phpstan-type Place array{path: string, typo3-version?: string, anchor: string, section?: string, kind: string, members?: list<string>}
 * @phpstan-type IndexedClass array{type?: string, places: list<Place>}
 */
final class ClassIndex
{
    /**
     * The namespaces an example invents for the reader to replace with their
     * own, from the "example-vendors" setting. A class below them exists
     * nowhere, so naming where it is spoken of says nothing.
     *
     * @var list<string>
     */
    private readonly array $exampleVendors;

    /**
     * The namespaces the index keeps, from the "indexed-namespaces" setting.
     * Empty for a manual that keeps every class it speaks of.
     *
     * @var list<string>
     */
    private readonly array $indexedNamespaces;

    /**
     * Whether this is the Changelog, whose places carry the release of their
     * entry. Another manual may have a "Changelog/" directory of its own.
     */
    private readonly bool $isChangelog;

    public function __construct(
        private readonly AnchorNormalizer $anchorNormalizer,
        private readonly UseStatements $useStatements,
        private readonly Typo3ApiService $typo3ApiService,
        private readonly ChangelogEntry $changelogEntry,
        Typo3DocsThemeSettings $themeSettings,
    ) {
        $vendors = $this->names($themeSettings->getSettings('example_vendors'));

        $this->exampleVendors = $vendors;
        $this->indexedNamespaces = $this->names($themeSettings->getSettings('indexed_namespaces'));
        $this->isChangelog = $themeSettings->getSettings('interlink_shortcode') === 'changelog';
    }

    /**
     * A setting that lists namespaces, as the list it means.
     *
     * @return list<string>
     */
    private function names(string $setting): array
    {
        $names = [];
        foreach (explode(',', $setting) as $name) {
            $name = trim($name, " \t\n\r\0\x0B\\");
            if ($name === '') {
                continue;
            }

            $names[] = $name;
        }

        return $names;
    }

    /**
     * @param iterable<DocumentNode> $documents
     * @return array<string, IndexedClass> what each class is and where it is spoken of, by its fully qualified name
     */
    public function of(iterable $documents): array
    {
        $classes = [];
        foreach ($documents as $document) {
            $place = ['path' => $document->getFilePath()];
            $version = $this->isChangelog ? $this->changelogEntry->version($document) : '';
            if ($version !== '') {
                $place['typo3-version'] = $version;
            }

            $place['anchor'] = $this->labelOf($document);
            $this->walk($document, $place, $classes);
        }

        ksort($classes);

        $index = [];
        foreach ($classes as $name => $places) {
            $places = array_values($places);
            usort($places, static fn(array $a, array $b): int => [$a['path'], $a['anchor'], $a['section'] ?? '', $a['kind']]
                <=> [$b['path'], $b['anchor'], $b['section'] ?? '', $b['kind']]);

            $type = $this->typo3ApiService->getClassInfo($name)['type'] ?? '';
            $index[$name] = ($type === '' ? [] : ['type' => $type]) + ['places' => $places];
        }

        return $index;
    }

    /**
     * @param Position $place where the node stands: path, release, anchor and section
     * @param array<string, array<string, Place>> $classes
     */
    private function walk(Node $node, array $place, array &$classes): void
    {
        if ($node instanceof SectionNode) {
            // A section with a label of its own is where a link lands. One
            // without is named beside the nearest label, which a permalink
            // resolves and its id does not.
            $label = $this->labelOf($node);
            unset($place['section']);
            if ($label !== '') {
                $place['anchor'] = $label;
            } elseif ($node->getId() !== '') {
                $place['section'] = $this->anchorNormalizer->reduceAnchor($node->getId());
            }
        }

        $this->record($node, $place, $classes);

        if (!$node instanceof CompoundNode) {
            return;
        }

        foreach ($node->getChildren() as $child) {
            $this->walk($child, $place, $classes);
        }
    }

    /**
     * @param Position $place
     * @param array<string, array<string, Place>> $classes
     */
    private function record(Node $node, array $place, array &$classes): void
    {
        if ($node instanceof CodeInlineNode) {
            $fqn = $node->getInfo()['fqn'] ?? '';
            if ($fqn !== '') {
                $this->add($classes, $fqn, $place + ['kind' => 'inline'], $node->getInfo()['member'] ?? '');
            }

            return;
        }

        if ($node instanceof CodeNode) {
            foreach ($this->useStatements->of($node) as $fqn) {
                // "use TYPO3\CMS\Extbase\Attribute as Extbase;" imports a
                // namespace, not a class, as a class role naming one does.
                $name = '\\' . ltrim($fqn, '\\');
                if ($this->typo3ApiService->getClassInfo($name) === [] && $this->typo3ApiService->isNamespace($name)) {
                    continue;
                }
                $this->add($classes, $fqn, $place + ['kind' => 'code']);
            }

            return;
        }

        if (!$node instanceof PhpComponentNode) {
            return;
        }

        // The class has an anchor of its own, which is where a reader of the
        // index wants to land, not at the section that holds it.
        unset($place['section']);
        $place['anchor'] = $this->anchorNormalizer->reduceAnchor($node->getId());
        $this->add($classes, $node->toString(), $place + ['kind' => 'definition']);
    }

    /**
     * @param array<string, array<string, Place>> $classes
     * @param Place $place
     */
    private function add(array &$classes, string $fqn, array $place, string $member = ''): void
    {
        $name = '\\' . ltrim($fqn, '\\');
        if (in_array(explode('\\', $name)[1] ?? '', $this->exampleVendors, true)) {
            return;
        }

        if ($this->indexedNamespaces !== [] && !$this->isIndexed($name)) {
            return;
        }

        // The same class named twice in one section is one place, however
        // often an author repeats it, and so is a section naming several of
        // its members: the place lists them.
        $key = implode("\0", [$place['path'], $place['anchor'], $place['section'] ?? '', $place['kind']]);
        $classes[$name][$key] ??= $place;
        if ($member === '') {
            return;
        }

        $members = $classes[$name][$key]['members'] ?? [];
        if (!in_array($member, $members, true)) {
            $members[] = $member;
            sort($members);
        }

        $classes[$name][$key]['members'] = $members;
    }

    /** Whether the class is below one of the namespaces the index keeps. */
    private function isIndexed(string $name): bool
    {
        foreach ($this->indexedNamespaces as $namespace) {
            if (str_starts_with($name, '\\' . $namespace . '\\')) {
                return true;
            }
        }

        return false;
    }

    /**
     * The label of this section or document, or "" where it has none.
     *
     * @param CompoundNode<Node> $node
     */
    private function labelOf(CompoundNode $node): string
    {
        foreach ($node->getChildren() as $child) {
            if ($child instanceof AnchorNode) {
                return $this->anchorNormalizer->reduceAnchor($child->toString());
            }

            if ($child instanceof SectionNode && $node instanceof DocumentNode) {
                // The document's own label sits inside its first section.
                foreach ($child->getChildren() as $sectionChild) {
                    if ($sectionChild instanceof AnchorNode) {
                        return $this->anchorNormalizer->reduceAnchor($sectionChild->toString());
                    }
                }

                break;
            }
        }

        return '';
    }
}
