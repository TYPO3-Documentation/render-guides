<?php

declare(strict_types=1);

/**
 * This file is part of phpDocumentor.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @link https://phpdoc.org
 */

namespace T3Docs\Typo3DocsTheme\Compiler\NodeTransformers;

use phpDocumentor\Guides\Compiler\CompilerContextInterface;
use phpDocumentor\Guides\Compiler\NodeTransformer;
use phpDocumentor\Guides\Nodes\DocumentTree\DocumentEntryNode;
use phpDocumentor\Guides\Nodes\DocumentTree\ExternalEntryNode;
use phpDocumentor\Guides\Nodes\Menu\MenuEntryNode;
use phpDocumentor\Guides\Nodes\Menu\TocNode;
use phpDocumentor\Guides\Nodes\Node;

use function array_key_first;
use function array_values;
use function count;
use function is_array;
use function ksort;

/**
 * Realigns the navigation menu entries of a document with the authored order of
 * its toctree.
 *
 * The guides compiler attaches a document's menu entries via separate
 * transformers for internal and external entries, each running in its own full
 * tree traversal. As a result, external entries are grouped together instead of
 * staying in the order in which they were written in the toctree, so the
 * sidebar navigation disagrees with the order shown in the on-page toctree.
 *
 * This downstream transformer reorders the menu entries to follow the toctree
 * again. It can be dropped once the fix lands upstream in phpdocumentor/guides.
 *
 * @see https://github.com/TYPO3-Documentation/render-guides/issues/1175
 *
 * @implements NodeTransformer<TocNode>
 */
final class SortMenuEntriesByToctreeTransformer implements NodeTransformer
{
    public function enterNode(Node $node, CompilerContextInterface $compilerContext): Node
    {
        if (!$node instanceof TocNode) {
            return $node;
        }

        // Globbed toctrees derive their order from the glob expansion, not from
        // an authored sequence, so they must not be realigned.
        if ($node->hasOption('glob')) {
            return $node;
        }

        $entries = $node->getValue();
        if (!is_array($entries)) {
            return $node;
        }

        $documentEntry = $compilerContext->getDocumentNode()->getDocumentEntry();
        $documentEntry->setMenuEntries(
            $this->sortMenuEntriesByToctree($entries, $documentEntry->getMenuEntries()),
        );

        return $node;
    }

    /**
     * Reorders the menu entries that belong to this toctree so they follow the
     * authored toctree order. The toctree's entries are emitted as one contiguous
     * block at the position of its first entry; entries belonging to other
     * toctrees of the same document keep their relative position. Applied per
     * toctree in document order, this yields the global authored order even when
     * a document has several toctrees.
     *
     * @param array<MenuEntryNode> $tocEntries
     * @param array<DocumentEntryNode|ExternalEntryNode> $menuEntries
     *
     * @return array<DocumentEntryNode|ExternalEntryNode>
     */
    private function sortMenuEntriesByToctree(array $tocEntries, array $menuEntries): array
    {
        // Map each authored toctree entry to its position. The key match relies
        // on the entry urls having been resolved to the document file (internal)
        // or external url by the attach transformers (priority 4500), which run
        // before this pass.
        $order = [];
        $position = 0;
        foreach ($tocEntries as $tocEntry) {
            if (!($tocEntry instanceof MenuEntryNode)) {
                continue;
            }

            $order[$tocEntry->getUrl()] = $position++;
        }

        $ordered = [];
        $matchedIndexes = [];
        foreach ($menuEntries as $index => $menuEntry) {
            $key = self::menuEntryKey($menuEntry);
            if (!isset($order[$key])) {
                continue;
            }

            $ordered[$order[$key]] = $menuEntry;
            $matchedIndexes[$index] = true;
        }

        // Safety: bail out when entries do not map one-to-one (e.g. the rare case
        // of duplicate entries within a single toctree).
        if ($matchedIndexes === [] || count($ordered) !== count($matchedIndexes)) {
            return $menuEntries;
        }

        ksort($ordered);
        $ordered = array_values($ordered);
        $firstIndex = array_key_first($matchedIndexes);

        $result = [];
        foreach ($menuEntries as $index => $menuEntry) {
            if ($index === $firstIndex) {
                foreach ($ordered as $orderedEntry) {
                    $result[] = $orderedEntry;
                }
            }

            if (isset($matchedIndexes[$index])) {
                continue;
            }

            $result[] = $menuEntry;
        }

        return $result;
    }

    private static function menuEntryKey(DocumentEntryNode|ExternalEntryNode $entry): string
    {
        return $entry instanceof DocumentEntryNode ? $entry->getFile() : $entry->getValue();
    }

    public function leaveNode(Node $node, CompilerContextInterface $compilerContext): Node
    {
        return $node;
    }

    public function supports(Node $node): bool
    {
        return $node instanceof TocNode;
    }

    public function getPriority(): int
    {
        // After the menu entries are attached (priority 4500) and after the
        // engine's ToctreeSortingTransformer (3200) has handled reversed
        // toctrees, so we realign whatever order they ended up in.
        return 3150;
    }
}
