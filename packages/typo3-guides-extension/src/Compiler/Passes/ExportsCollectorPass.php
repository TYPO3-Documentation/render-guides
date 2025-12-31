<?php

declare(strict_types=1);

namespace T3Docs\GuidesExtension\Compiler\Passes;

use phpDocumentor\Guides\Compiler\CompilerContextInterface;
use phpDocumentor\Guides\Compiler\CompilerPass;
use phpDocumentor\Guides\Nodes\DocumentNode;
use phpDocumentor\Guides\Nodes\Node;
use phpDocumentor\Guides\Nodes\ProjectNode;
use phpDocumentor\Guides\Nodes\SectionNode;
use T3Docs\GuidesExtension\Compiler\Cache\ContentHasher;
use T3Docs\GuidesExtension\Compiler\Cache\DocumentExports;
use T3Docs\GuidesExtension\Compiler\Cache\IncrementalBuildCache;

/**
 * Collects exports (anchors, titles, citations) from each document
 * for incremental rendering dependency tracking.
 *
 * Priority 4500: After CollectLinkTargetsTransformer (5000) but before rendering.
 */
final class ExportsCollectorPass implements CompilerPass
{
    public function __construct(
        private readonly IncrementalBuildCache $cache,
        private readonly ContentHasher $hasher,
    ) {}

    public function getPriority(): int
    {
        return 4500;
    }

    /**
     * @param DocumentNode[] $documents
     * @return DocumentNode[]
     */
    public function run(array $documents, CompilerContextInterface $compilerContext): array
    {
        $projectNode = $compilerContext->getProjectNode();

        // Get input directory for file path resolution
        $inputDir = getcwd() . '/Documentation'; // Fallback

        foreach ($documents as $document) {
            $docPath = $document->getFilePath();

            // Collect anchors from this document
            $anchors = $this->collectAnchors($document, $projectNode);

            // Collect section titles
            $sectionTitles = $this->collectSectionTitles($document);

            // Collect citations (if any)
            $citations = $this->collectCitations($document, $projectNode);

            // Compute content hash and mtime from the actual source file
            $sourceFilePath = $inputDir . '/' . $docPath . '.rst';
            $contentHash = '';
            $lastModified = 0;

            // Try to find the actual source file
            if (file_exists($sourceFilePath)) {
                $contentHash = $this->hasher->hashFile($sourceFilePath);
                $lastModified = (int) filemtime($sourceFilePath);
            } else {
                // Try common extensions
                foreach (['.rst', '.md'] as $ext) {
                    $tryPath = $inputDir . '/' . $docPath . $ext;
                    if (file_exists($tryPath)) {
                        $contentHash = $this->hasher->hashFile($tryPath);
                        $lastModified = (int) filemtime($tryPath);
                        break;
                    }
                }
            }

            // Final fallback: hash the document structure
            if ($contentHash === '') {
                $contentHash = $this->hasher->hashContent(serialize($document));
                $lastModified = time();
            }

            $exportsHash = $this->hasher->hashExports($anchors, $sectionTitles, $citations);

            $exports = new DocumentExports(
                documentPath: $docPath,
                contentHash: $contentHash,
                exportsHash: $exportsHash,
                anchors: $anchors,
                sectionTitles: $sectionTitles,
                citations: $citations,
                lastModified: $lastModified,
            );

            $this->cache->setExports($docPath, $exports);
        }

        return $documents;
    }

    /**
     * Collect all anchors defined in this document.
     *
     * @return array<string, string> Anchor name => title
     */
    private function collectAnchors(DocumentNode $document, ProjectNode $projectNode): array
    {
        /** @var array<string, string> $anchors */
        $anchors = [];
        $filePath = $document->getFilePath();

        // Get all internal targets from the project node for this document
        $allTargets = $projectNode->getAllInternalTargets();

        foreach ($allTargets as $targets) {
            foreach ($targets as $anchorName => $target) {
                if ($target->getDocumentPath() === $filePath) {
                    $anchors[(string) $anchorName] = $target->getTitle() ?? (string) $anchorName;
                }
            }
        }

        return $anchors;
    }

    /**
     * Collect section titles from this document.
     *
     * @return array<string, string> Section ID => title
     */
    private function collectSectionTitles(DocumentNode $document): array
    {
        $titles = [];

        $this->traverseNodes(array_values($document->getChildren()), function (Node $node) use (&$titles) {
            if ($node instanceof SectionNode) {
                $titles[$node->getId()] = $node->getTitle()->toString();
            }
        });

        return $titles;
    }

    /**
     * Collect citations defined in this document.
     *
     * @return string[]
     */
    private function collectCitations(DocumentNode $document, ProjectNode $projectNode): array
    {
        // Check all citation targets in project
        // This is a simplified approach - ideally we'd traverse the document
        // to find FootnoteCitationNode or similar

        return [];
    }

    /**
     * Traverse all nodes recursively.
     *
     * @param list<Node> $nodes
     * @param callable(Node): void $callback
     */
    private function traverseNodes(array $nodes, callable $callback): void
    {
        foreach ($nodes as $node) {
            $callback($node);

            if (method_exists($node, 'getChildren')) {
                $children = $node->getChildren();
                if (is_array($children)) {
                    $this->traverseNodes(array_values($children), $callback);
                }
            }
        }
    }
}
