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
use phpDocumentor\Guides\Nodes\CompoundNode;
use phpDocumentor\Guides\Nodes\Node;
use phpDocumentor\Guides\RestructuredText\Nodes\ConfvalNode;
use Psr\Log\LoggerInterface;
use T3Docs\Typo3DocsTheme\Nodes\ConfvalMenuNode;

use function assert;

/** @implements NodeTransformer<ConfvalMenuNode> */
final class ConfvalMenuNodeTransformer implements NodeTransformer
{
    public function __construct(
        private readonly LoggerInterface $logger,
    ) {}

    public function enterNode(Node $node, CompilerContextInterface $compilerContext): Node
    {
        return $node;
    }

    public function leaveNode(Node $node, CompilerContextInterface $compilerContext): Node
    {
        assert($node instanceof ConfvalMenuNode);
        if (count($node->getConfvals()) > 0) {
            return $node;
        }
        $confvals = $this->findConfvals($compilerContext->getDocumentNode(), $node);
        if (count($confvals) < 1) {
            $this->logger->warning('No confvals found for the confval-menu', $compilerContext->getLoggerInformation());
        }
        $node->setConfvals($confvals);

        return $node;
    }

    /**
     * @return ConfvalNode[]
     */
    private function findConfvals(Node $node, ConfvalMenuNode $confvalMenuNode): array
    {
        if ($node instanceof ConfvalNode) {
            if ($confvalMenuNode->isExcludeNoindex() && $node->isNoindex()) {
                return [];
            }
            if (in_array($node->getId(), $confvalMenuNode->getExclude(), true)) {
                return [];
            }
            return [$node];
        }
        if ($node instanceof CompoundNode) {
            $confvalNodes = [];
            foreach ($node->getChildren() as $child) {
                $confvalNodes = array_merge($confvalNodes, $this->findConfvals($child, $confvalMenuNode));
            }
            return $confvalNodes;
        }
        return [];
    }

    public function supports(Node $node): bool
    {
        return $node instanceof ConfvalMenuNode;
    }

    public function getPriority(): int
    {
        return 1000;
    }
}
