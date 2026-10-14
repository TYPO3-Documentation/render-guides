<?php

declare(strict_types=1);

namespace T3Docs\Typo3DocsTheme\Compiler\NodeTransformers;

use phpDocumentor\Guides\Compiler\CompilerContextInterface;
use phpDocumentor\Guides\Compiler\NodeTransformer;
use phpDocumentor\Guides\Nodes\AnchorNode;
use phpDocumentor\Guides\Nodes\DocumentNode;
use phpDocumentor\Guides\Nodes\Node;
use phpDocumentor\Guides\ReferenceResolvers\AnchorNormalizer;
use Psr\Log\LoggerInterface;

use function sprintf;

/**
 * Warns about an anchor that a page declares twice.
 *
 * phpDocumentor/guides warns about an anchor that two pages declare, but not
 * about one that the same page declares twice: it accepts a second target in
 * the same document without a word, because the compiler may add a document a
 * second time. Only the first label then registers the anchor. Every link and
 * permalink leads to the first section, and the page carries the same id
 * twice.
 *
 * Labels are compared as the rendering uses them: "Building" and "building",
 * or "Setup_Docker" and "setup-docker", are the same anchor.
 *
 * The anchors seen are counted per document and start over with each one, so
 * a document that is compiled again is not reported.
 *
 * A stop-gap: phpDocumentor/guides warned about this itself until 1.9.2, when
 * commit ac9dccd4 let a document register its anchors again on a re-render.
 * Remove this check when guides warns again, or every duplicate is reported
 * twice.
 *
 * @implements NodeTransformer<DocumentNode|AnchorNode>
 */
final class CheckDuplicateAnchorsNodeTransformer implements NodeTransformer
{
    /** @var array<string, string> the label as written, by its anchor */
    private array $seen = [];

    public function __construct(
        private readonly AnchorNormalizer $anchorNormalizer,
        private readonly LoggerInterface $logger,
    ) {}

    public function enterNode(Node $node, CompilerContextInterface $compilerContext): Node
    {
        if ($node instanceof DocumentNode) {
            $this->seen = [];

            return $node;
        }

        if (!$node instanceof AnchorNode) {
            return $node;
        }

        $anchor = $this->anchorNormalizer->reduceAnchor($node->toString());
        if (!isset($this->seen[$anchor])) {
            $this->seen[$anchor] = $node->toString();

            return $node;
        }

        $this->logger->warning(sprintf(
            'The anchor "%s" is declared twice on this page (as "%s" and "%s"). Only the first one can be linked. Give the second section an anchor of its own.',
            $anchor,
            $this->seen[$anchor],
            $node->toString(),
        ), $compilerContext->getLoggerInformation());

        return $node;
    }

    public function leaveNode(Node $node, CompilerContextInterface $compilerContext): Node
    {
        return $node;
    }

    public function supports(Node $node): bool
    {
        return $node instanceof DocumentNode || $node instanceof AnchorNode;
    }

    public function getPriority(): int
    {
        return 800;
    }
}
