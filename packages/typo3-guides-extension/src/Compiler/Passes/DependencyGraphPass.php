<?php

declare(strict_types=1);

namespace T3Docs\GuidesExtension\Compiler\Passes;

use phpDocumentor\Guides\Compiler\CompilerContextInterface;
use phpDocumentor\Guides\Compiler\CompilerPass;
use phpDocumentor\Guides\Nodes\DocumentNode;
use phpDocumentor\Guides\Nodes\Inline\CrossReferenceNode;
use phpDocumentor\Guides\Nodes\Inline\DocReferenceNode;
use phpDocumentor\Guides\Nodes\Inline\ReferenceNode;
use phpDocumentor\Guides\Nodes\Node;
use T3Docs\GuidesExtension\Compiler\Cache\IncrementalBuildCache;

/**
 * Builds the dependency graph by finding cross-references between documents.
 *
 * Tracks which documents import from which others, enabling proper
 * dirty propagation during incremental rendering.
 *
 * Priority 4000: After ExportsCollectorPass (4500).
 */
final class DependencyGraphPass implements CompilerPass
{
    public function __construct(
        private readonly IncrementalBuildCache $cache,
    ) {}

    public function getPriority(): int
    {
        return 4000;
    }

    /**
     * @param DocumentNode[] $documents
     * @return DocumentNode[]
     */
    public function run(array $documents, CompilerContextInterface $compilerContext): array
    {
        $projectNode = $compilerContext->getProjectNode();
        $graph = $this->cache->getDependencyGraph();

        foreach ($documents as $document) {
            $filePath = $document->getFilePath();

            // Clear old imports for this document
            $graph->clearImportsFor($filePath);

            // Find all references in this document
            $imports = $this->findImports($document, $projectNode);

            // Add edges to the graph
            foreach ($imports as $importedDocPath) {
                $graph->addImport($filePath, $importedDocPath);
            }
        }

        return $documents;
    }

    /**
     * Find all documents that this document imports from.
     *
     * @return string[] Imported document paths
     */
    private function findImports(DocumentNode $document, $projectNode): array
    {
        $imports = [];
        $filePath = $document->getFilePath();

        $this->traverseNodes($document->getChildren(), function (Node $node) use (&$imports, $projectNode, $filePath) {
            // Handle :doc:`reference`
            if ($node instanceof DocReferenceNode) {
                $targetDoc = $this->resolveDocReference($node, $projectNode);
                if ($targetDoc !== null && $targetDoc !== $filePath) {
                    $imports[] = $targetDoc;
                }
                return;
            }

            // Handle :ref:`reference`
            if ($node instanceof ReferenceNode) {
                $targetDoc = $this->resolveRefReference($node, $projectNode);
                if ($targetDoc !== null && $targetDoc !== $filePath) {
                    $imports[] = $targetDoc;
                }
                return;
            }

            // Handle any other CrossReferenceNode
            if ($node instanceof CrossReferenceNode) {
                $targetDoc = $this->resolveCrossReference($node, $projectNode);
                if ($targetDoc !== null && $targetDoc !== $filePath) {
                    $imports[] = $targetDoc;
                }
            }
        });

        return array_unique($imports);
    }

    /**
     * Resolve a :doc: reference to its target document.
     */
    private function resolveDocReference(DocReferenceNode $node, $projectNode): ?string
    {
        // The target is the document path
        $target = $node->getTargetReference();

        // Strip any anchor
        $hashPos = strpos($target, '#');
        if ($hashPos !== false) {
            $target = substr($target, 0, $hashPos);
        }

        // Check if document exists in project
        try {
            $entry = $projectNode->findDocumentEntry($target);
            if ($entry !== null) {
                return $entry->getFile();
            }
        } catch (\Throwable) {
            // Document not found
        }

        return $target ?: null;
    }

    /**
     * Resolve a :ref: reference to its target document.
     */
    private function resolveRefReference(ReferenceNode $node, $projectNode): ?string
    {
        $targetAnchor = $node->getTargetReference();
        $linkType = $node->getLinkType();

        // Look up the anchor in the project's internal targets
        $target = $projectNode->getInternalTarget($targetAnchor, $linkType);

        if ($target !== null) {
            return $target->getDocumentPath();
        }

        // Try default link type
        $target = $projectNode->getInternalTarget($targetAnchor);

        return $target?->getDocumentPath();
    }

    /**
     * Resolve any CrossReferenceNode to its target document.
     */
    private function resolveCrossReference(CrossReferenceNode $node, $projectNode): ?string
    {
        // For interlink references to external projects, we don't track dependencies
        if ($node->getInterlinkDomain() !== '') {
            return null;
        }

        // Try to resolve using the reference target
        $targetAnchor = $node->getTargetReference();

        $target = $projectNode->getInternalTarget($targetAnchor);
        if ($target !== null) {
            return $target->getDocumentPath();
        }

        return null;
    }

    /**
     * Traverse all nodes recursively, including inline nodes.
     *
     * @param Node[]|iterable $nodes
     * @param callable(Node): void $callback
     */
    private function traverseNodes(iterable $nodes, callable $callback): void
    {
        foreach ($nodes as $node) {
            $callback($node);

            // Traverse children
            if (method_exists($node, 'getChildren')) {
                $children = $node->getChildren();
                if (is_iterable($children)) {
                    $this->traverseNodes($children, $callback);
                }
            }

            // Traverse inline nodes in value (for certain node types)
            if (method_exists($node, 'getValue')) {
                $value = $node->getValue();
                if ($value instanceof Node) {
                    $callback($value);
                } elseif (is_iterable($value)) {
                    $this->traverseNodes($value, $callback);
                }
            }
        }
    }
}
