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
use function sprintf;

/** @implements NodeTransformer<ConfvalMenuNode> */
final class ConfvalMenuNodeTransformer implements NodeTransformer
{
    /** @var array<string, string> the parent a menu gave each option, by document and option */
    private array $parents = [];

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
        if (count($node->getConfvals()) === 0) {
            $confvals = $this->findConfvals($compilerContext->getDocumentNode(), $node);
            if (count($confvals) < 1) {
                $this->logger->warning('No confvals found for the confval-menu', $compilerContext->getLoggerInformation());
            }
            $node->setConfvals($confvals);
        }
        $this->noteParent($node, $compilerContext);

        return $node;
    }

    /**
     * A menu with ":parent:" makes the options it lists properties of that
     * option. @see \T3Docs\Typo3DocsTheme\ConfvalIndex\ConfvalIndex
     *
     * An option listed by two menus with different parents keeps the first:
     * it can be the property of one option only.
     */
    private function noteParent(ConfvalMenuNode $node, CompilerContextInterface $compilerContext): void
    {
        $parent = $node->getParent();
        if ($parent === '') {
            return;
        }
        $document = $compilerContext->getDocumentNode()->getFilePath();
        foreach ($node->getConfvals() as $confval) {
            $key = $document . '#' . $confval->getId();
            $first = $this->parents[$key] ??= $parent;
            if ($first !== $parent) {
                $this->logger->warning(sprintf(
                    'The confval "%s" is listed by two confval-menus with different parents, "%s" and "%s". It stays a property of "%s".',
                    $confval->getId(),
                    $first,
                    $parent,
                    $first,
                ), $compilerContext->getLoggerInformation());
            }
        }
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
            // The option whose properties the menu lists is none of them.
            if ($node->getId() === $confvalMenuNode->getParent()) {
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
