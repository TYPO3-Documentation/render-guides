<?php

declare(strict_types=1);

namespace T3Docs\GuidesExtension\Compiler\Cache;

/**
 * Propagates dirty state through the dependency graph.
 *
 * When a document's exports change, all documents that import from it
 * must also be re-rendered to update their cross-references.
 */
final class DirtyPropagator
{
    /**
     * Propagate dirty state and compute final render set.
     *
     * @param ChangeDetectionResult $changes Initial change detection
     * @param DependencyGraph $graph Dependency relationships
     * @param array<string, DocumentExports> $oldExports Previous build's exports
     * @param array<string, DocumentExports> $newExports Current build's exports (for dirty docs)
     * @return PropagationResult
     */
    public function propagate(
        ChangeDetectionResult $changes,
        DependencyGraph $graph,
        array $oldExports,
        array $newExports,
    ): PropagationResult {
        // Start with directly dirty/new documents
        $dirtySet = array_flip(array_merge($changes->dirty, $changes->new));
        $propagatedFrom = [];

        // Handle deleted files - their dependents become dirty
        foreach ($changes->deleted as $deletedPath) {
            $dependents = $graph->getDependents($deletedPath);
            foreach ($dependents as $dependent) {
                if (!isset($dirtySet[$dependent])) {
                    $dirtySet[$dependent] = true;
                    $propagatedFrom[] = $deletedPath;
                }
            }
        }

        // Check if exports changed for dirty docs
        // If so, propagate to dependents
        $queue = array_keys($dirtySet);
        $visited = [];

        while (!empty($queue)) {
            $current = array_shift($queue);

            if (isset($visited[$current])) {
                continue;
            }
            $visited[$current] = true;

            // Check if exports changed
            $old = $oldExports[$current] ?? null;
            $new = $newExports[$current] ?? null;

            $exportsChanged = false;
            if ($old === null || $new === null) {
                // New or deleted - definitely changed
                $exportsChanged = true;
            } elseif ($old->hasExportsChanged($new)) {
                $exportsChanged = true;
            }

            if ($exportsChanged) {
                // Propagate to dependents
                foreach ($graph->getDependents($current) as $dependent) {
                    if (!isset($dirtySet[$dependent])) {
                        $dirtySet[$dependent] = true;
                        $propagatedFrom[] = $current;

                        // Add to queue for further propagation
                        if (!isset($visited[$dependent])) {
                            $queue[] = $dependent;
                        }
                    }
                }
            }
        }

        // Compute final sets
        $documentsToRender = array_keys($dirtySet);
        $documentsToSkip = array_diff($changes->clean, $documentsToRender);

        return new PropagationResult(
            documentsToRender: array_values($documentsToRender),
            documentsToSkip: array_values($documentsToSkip),
            propagatedFrom: array_unique($propagatedFrom),
        );
    }

    /**
     * Simple propagation without export comparison.
     * Used when exports aren't available yet (during initial compile).
     *
     * @param string[] $dirtyDocs Initially dirty documents
     * @param DependencyGraph $graph Dependency relationships
     * @return string[] All documents that need rendering
     */
    public function propagateSimple(array $dirtyDocs, DependencyGraph $graph): array
    {
        return $graph->propagateDirty($dirtyDocs);
    }
}
