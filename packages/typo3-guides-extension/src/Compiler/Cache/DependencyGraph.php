<?php

declare(strict_types=1);

namespace T3Docs\GuidesExtension\Compiler\Cache;

/**
 * Tracks inter-document dependencies for incremental rendering.
 *
 * When document A references an anchor in document B:
 * - A "imports" from B
 * - B has A as a "dependent"
 *
 * If B's exports change, A needs to be re-rendered.
 */
final class DependencyGraph
{
    /**
     * Forward edges: docPath -> [imported docPath => true]
     * "Document A imports from documents B, C, D"
     * Uses keyed arrays for O(1) lookup instead of in_array O(n).
     *
     * @var array<string, array<string, true>>
     */
    private array $imports = [];

    /**
     * Reverse edges: docPath -> [dependent docPath => true]
     * "Document B is depended on by documents A, E, F"
     * Uses keyed arrays for O(1) lookup instead of in_array O(n).
     *
     * @var array<string, array<string, true>>
     */
    private array $dependents = [];

    /**
     * Record that $fromDoc imports/references something from $toDoc.
     * O(1) operation using keyed arrays.
     */
    public function addImport(string $fromDoc, string $toDoc): void
    {
        // Don't add self-references
        if ($fromDoc === $toDoc) {
            return;
        }

        // Add forward edge (O(1) with isset check)
        $this->imports[$fromDoc][$toDoc] = true;

        // Add reverse edge (O(1) with isset check)
        $this->dependents[$toDoc][$fromDoc] = true;
    }

    /**
     * Get all documents that $docPath imports from.
     *
     * @return string[]
     */
    public function getImports(string $docPath): array
    {
        return array_keys($this->imports[$docPath] ?? []);
    }

    /**
     * Get all documents that depend on $docPath.
     *
     * @return string[]
     */
    public function getDependents(string $docPath): array
    {
        return array_keys($this->dependents[$docPath] ?? []);
    }

    /**
     * Given a set of dirty documents, propagate to find all affected documents.
     * Uses transitive closure: if A depends on B, and B is dirty, A is dirty.
     *
     * Optimized to O(V+E) using SplQueue for O(1) dequeue operations.
     *
     * @param string[] $dirtyDocs Initially dirty documents
     * @return string[] All documents that need re-rendering
     */
    public function propagateDirty(array $dirtyDocs): array
    {
        $result = [];
        $visited = [];

        // Use SplQueue for O(1) enqueue/dequeue instead of array_shift O(n)
        $queue = new \SplQueue();
        foreach ($dirtyDocs as $doc) {
            $queue->enqueue($doc);
        }

        while (!$queue->isEmpty()) {
            $current = $queue->dequeue();

            if (isset($visited[$current])) {
                continue;
            }
            $visited[$current] = true;
            $result[] = $current;

            // Add all dependents to the queue
            foreach ($this->getDependents($current) as $dependent) {
                if (!isset($visited[$dependent])) {
                    $queue->enqueue($dependent);
                }
            }
        }

        return $result; // No array_unique needed - visited check prevents duplicates
    }

    /**
     * Remove a document from the graph (when deleted).
     * O(E) where E is edges involving this document.
     */
    public function removeDocument(string $docPath): void
    {
        // Remove references to this doc from other entries (before removing own entries)
        // Use keyed lookup for O(1) unset
        foreach ($this->imports as $from => $toList) {
            unset($this->imports[$from][$docPath]);
            if ($this->imports[$from] === []) {
                unset($this->imports[$from]);
            }
        }

        foreach ($this->dependents as $to => $fromList) {
            unset($this->dependents[$to][$docPath]);
            if ($this->dependents[$to] === []) {
                unset($this->dependents[$to]);
            }
        }

        // Remove own entries
        unset($this->imports[$docPath]);
        unset($this->dependents[$docPath]);
    }

    /**
     * Clear all edges for a document (before re-computing).
     * O(I) where I is number of imports for this document.
     */
    public function clearImportsFor(string $docPath): void
    {
        // Get old imports before clearing
        $oldImports = $this->imports[$docPath] ?? [];
        unset($this->imports[$docPath]);

        // Remove this doc from dependents of its old imports (O(1) per import)
        foreach (array_keys($oldImports) as $importedDoc) {
            unset($this->dependents[$importedDoc][$docPath]);
            if (isset($this->dependents[$importedDoc]) && $this->dependents[$importedDoc] === []) {
                unset($this->dependents[$importedDoc]);
            }
        }
    }

    /**
     * Get all document paths in the graph.
     *
     * @return string[]
     */
    public function getAllDocuments(): array
    {
        return array_unique(array_merge(
            array_keys($this->imports),
            array_keys($this->dependents)
        ));
    }

    /**
     * Serialize to array for JSON persistence.
     * Converts keyed arrays to value arrays for compact storage.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        // Convert keyed arrays to value arrays for JSON storage
        $imports = [];
        foreach ($this->imports as $from => $toMap) {
            $imports[$from] = array_keys($toMap);
        }

        $dependents = [];
        foreach ($this->dependents as $to => $fromMap) {
            $dependents[$to] = array_keys($fromMap);
        }

        return [
            'imports' => $imports,
            'dependents' => $dependents,
        ];
    }

    /**
     * Deserialize from array.
     * Converts value arrays back to keyed arrays for O(1) lookups.
     *
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        $graph = new self();

        // Convert value arrays to keyed arrays for O(1) lookup
        foreach ($data['imports'] ?? [] as $from => $toList) {
            if (is_array($toList)) {
                $graph->imports[$from] = array_fill_keys($toList, true);
            }
        }

        foreach ($data['dependents'] ?? [] as $to => $fromList) {
            if (is_array($fromList)) {
                $graph->dependents[$to] = array_fill_keys($fromList, true);
            }
        }

        return $graph;
    }

    /**
     * Get statistics about the graph.
     *
     * @return array<string, int>
     */
    public function getStats(): array
    {
        $totalEdges = 0;
        foreach ($this->imports as $edges) {
            $totalEdges += count($edges);
        }

        return [
            'documents' => count($this->getAllDocuments()),
            'edges' => $totalEdges,
            'avgImportsPerDoc' => $totalEdges > 0 ? $totalEdges / max(1, count($this->imports)) : 0,
        ];
    }

    /**
     * Merge another dependency graph into this one.
     * O(E) where E is number of edges in the other graph.
     *
     * Used to combine results from parallel child processes.
     */
    public function merge(self $other): void
    {
        // Merge imports using array union (O(1) per entry with keyed arrays)
        foreach ($other->imports as $from => $toMap) {
            if (!isset($this->imports[$from])) {
                $this->imports[$from] = $toMap;
            } else {
                // Union of keyed arrays - O(n) but no in_array lookups
                $this->imports[$from] += $toMap;
            }
        }

        // Merge dependents using array union
        foreach ($other->dependents as $to => $fromMap) {
            if (!isset($this->dependents[$to])) {
                $this->dependents[$to] = $fromMap;
            } else {
                $this->dependents[$to] += $fromMap;
            }
        }
    }
}
