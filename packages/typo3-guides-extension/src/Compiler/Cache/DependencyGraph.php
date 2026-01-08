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
     * Forward edges: docPath -> [imported docPaths]
     * "Document A imports from documents B, C, D"
     *
     * @var array<string, string[]>
     */
    private array $imports = [];

    /**
     * Reverse edges: docPath -> [dependent docPaths]
     * "Document B is depended on by documents A, E, F"
     *
     * @var array<string, string[]>
     */
    private array $dependents = [];

    /**
     * Record that $fromDoc imports/references something from $toDoc.
     */
    public function addImport(string $fromDoc, string $toDoc): void
    {
        // Don't add self-references
        if ($fromDoc === $toDoc) {
            return;
        }

        // Add forward edge
        if (!isset($this->imports[$fromDoc])) {
            $this->imports[$fromDoc] = [];
        }
        if (!in_array($toDoc, $this->imports[$fromDoc], true)) {
            $this->imports[$fromDoc][] = $toDoc;
        }

        // Add reverse edge
        if (!isset($this->dependents[$toDoc])) {
            $this->dependents[$toDoc] = [];
        }
        if (!in_array($fromDoc, $this->dependents[$toDoc], true)) {
            $this->dependents[$toDoc][] = $fromDoc;
        }
    }

    /**
     * Get all documents that $docPath imports from.
     *
     * @return string[]
     */
    public function getImports(string $docPath): array
    {
        return $this->imports[$docPath] ?? [];
    }

    /**
     * Get all documents that depend on $docPath.
     *
     * @return string[]
     */
    public function getDependents(string $docPath): array
    {
        return $this->dependents[$docPath] ?? [];
    }

    /**
     * Given a set of dirty documents, propagate to find all affected documents.
     * Uses transitive closure: if A depends on B, and B is dirty, A is dirty.
     *
     * @param string[] $dirtyDocs Initially dirty documents
     * @return string[] All documents that need re-rendering
     */
    public function propagateDirty(array $dirtyDocs): array
    {
        $result = [];
        $visited = [];
        $queue = $dirtyDocs;

        while (!empty($queue)) {
            $current = array_shift($queue);

            if (isset($visited[$current])) {
                continue;
            }
            $visited[$current] = true;
            $result[] = $current;

            // Add all dependents to the queue
            foreach ($this->getDependents($current) as $dependent) {
                if (!isset($visited[$dependent])) {
                    $queue[] = $dependent;
                }
            }
        }

        return array_unique($result);
    }

    /**
     * Remove a document from the graph (when deleted).
     */
    public function removeDocument(string $docPath): void
    {
        // Remove from imports
        unset($this->imports[$docPath]);

        // Remove from dependents
        unset($this->dependents[$docPath]);

        // Remove references to this doc from other entries
        foreach ($this->imports as $from => $toList) {
            $this->imports[$from] = array_values(array_filter(
                $toList,
                fn($to) => $to !== $docPath
            ));
        }

        foreach ($this->dependents as $to => $fromList) {
            $this->dependents[$to] = array_values(array_filter(
                $fromList,
                fn($from) => $from !== $docPath
            ));
        }
    }

    /**
     * Clear all edges for a document (before re-computing).
     */
    public function clearImportsFor(string $docPath): void
    {
        // Remove this doc's imports
        $oldImports = $this->imports[$docPath] ?? [];
        unset($this->imports[$docPath]);

        // Remove this doc from dependents of its old imports
        foreach ($oldImports as $importedDoc) {
            if (isset($this->dependents[$importedDoc])) {
                $this->dependents[$importedDoc] = array_values(array_filter(
                    $this->dependents[$importedDoc],
                    fn($dep) => $dep !== $docPath
                ));
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
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'imports' => $this->imports,
            'dependents' => $this->dependents,
        ];
    }

    /**
     * Deserialize from array.
     *
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        $graph = new self();
        $graph->imports = $data['imports'] ?? [];
        $graph->dependents = $data['dependents'] ?? [];
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
     *
     * Used to combine results from parallel child processes.
     */
    public function merge(self $other): void
    {
        // Merge imports
        foreach ($other->imports as $from => $toList) {
            if (!isset($this->imports[$from])) {
                $this->imports[$from] = [];
            }
            foreach ($toList as $to) {
                if (!in_array($to, $this->imports[$from], true)) {
                    $this->imports[$from][] = $to;
                }
            }
        }

        // Merge dependents
        foreach ($other->dependents as $to => $fromList) {
            if (!isset($this->dependents[$to])) {
                $this->dependents[$to] = [];
            }
            foreach ($fromList as $from) {
                if (!in_array($from, $this->dependents[$to], true)) {
                    $this->dependents[$to][] = $from;
                }
            }
        }
    }
}
