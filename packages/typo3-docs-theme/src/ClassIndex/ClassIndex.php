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
use T3Docs\GuidesPhpDomain\Nodes\PhpComponentNode;
use T3Docs\Typo3DocsTheme\Nodes\Inline\CodeInlineNode;
use T3Docs\Typo3DocsTheme\Settings\Typo3DocsThemeSettings;

use function explode;
use function in_array;
use function ksort;
use function ltrim;
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
 * Each place names the page and the anchor of the section it stands in, so a
 * consumer can link to the passage rather than to the top of the page.
 *
 * A class the TYPO3 API knows carries what it is -- class, interface, trait or
 * enum. A name below "TYPO3" without one is a namespace, a typo or an
 * invention, and none of those can be told from the writing alone: a "use"
 * statement imports a namespace and a class alike.
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

    public function __construct(
        private readonly AnchorNormalizer $anchorNormalizer,
        private readonly UseStatements $useStatements,
        private readonly Typo3ApiService $typo3ApiService,
        Typo3DocsThemeSettings $themeSettings,
    ) {
        $vendors = $this->names($themeSettings->getSettings('example_vendors'));

        $this->exampleVendors = $vendors;
        $this->indexedNamespaces = $this->names($themeSettings->getSettings('indexed_namespaces'));
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
     * @return array<string, array<string, mixed>> what each class is and where it is spoken of, by its fully qualified name
     */
    public function of(iterable $documents): array
    {
        $classes = [];
        foreach ($documents as $document) {
            $this->walk($document, $document->getFilePath(), $this->anchorOf($document), $classes);
        }

        ksort($classes);

        $index = [];
        foreach ($classes as $name => $places) {
            usort($places, static fn(array $a, array $b): int => [$a['path'], $a['anchor'], $a['kind'], $a['member'] ?? '']
                <=> [$b['path'], $b['anchor'], $b['kind'], $b['member'] ?? '']);

            $type = $this->typo3ApiService->getClassInfo($name)['type'] ?? '';
            $index[$name] = ($type === '' ? [] : ['type' => $type]) + ['places' => $places];
        }

        return $index;
    }

    /**
     * @param array<string, list<array<string, string>>> $classes
     */
    private function walk(Node $node, string $path, string $anchor, array &$classes): void
    {
        if ($node instanceof SectionNode) {
            $anchor = $this->anchorOf($node);
        }

        $this->record($node, $path, $anchor, $classes);

        if (!$node instanceof CompoundNode) {
            return;
        }

        foreach ($node->getChildren() as $child) {
            $this->walk($child, $path, $anchor, $classes);
        }
    }

    /**
     * @param array<string, list<array<string, string>>> $classes
     */
    private function record(Node $node, string $path, string $anchor, array &$classes): void
    {
        if ($node instanceof CodeInlineNode) {
            $fqn = $node->getInfo()['fqn'] ?? '';
            if ($fqn !== '') {
                $member = $node->getInfo()['member'] ?? '';
                $this->add($classes, $fqn, ['path' => $path, 'anchor' => $anchor, 'kind' => 'role']
                    + ($member === '' ? [] : ['member' => $member]));
            }

            return;
        }

        if ($node instanceof CodeNode) {
            foreach ($this->useStatements->of($node) as $fqn) {
                $this->add($classes, $fqn, ['path' => $path, 'anchor' => $anchor, 'kind' => 'use']);
            }

            return;
        }

        if (!$node instanceof PhpComponentNode) {
            return;
        }

        $this->add($classes, $node->toString(), [
            'path' => $path,
            // The class has an anchor of its own, which is where a reader of
            // the index wants to land, not at the section that holds it.
            'anchor' => $this->anchorNormalizer->reduceAnchor($node->getId()),
            'kind' => 'definition',
        ]);
    }

    /**
     * @param array<string, list<array<string, string>>> $classes
     * @param array<string, string> $place
     */
    private function add(array &$classes, string $fqn, array $place): void
    {
        $name = '\\' . ltrim($fqn, '\\');
        if (in_array(explode('\\', $name)[1] ?? '', $this->exampleVendors, true)) {
            return;
        }

        if ($this->indexedNamespaces !== [] && !$this->isIndexed($name)) {
            return;
        }

        if (!isset($classes[$name])) {
            $classes[$name] = [];
        }

        // The same class named twice in one section is one place, however
        // often an author repeats it.
        if (in_array($place, $classes[$name], true)) {
            return;
        }

        $classes[$name][] = $place;
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
     * The anchor a link to this section or document uses: its explicit label
     * where it has one, and otherwise the id derived from its title.
     *
     * @param CompoundNode<Node> $node
     */
    private function anchorOf(CompoundNode $node): string
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

        if ($node instanceof SectionNode) {
            $id = $node->getId();

            return $id === '' ? '' : $this->anchorNormalizer->reduceAnchor($id);
        }

        return '';
    }
}
