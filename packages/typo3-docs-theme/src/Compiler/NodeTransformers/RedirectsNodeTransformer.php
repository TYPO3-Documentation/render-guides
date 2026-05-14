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
use phpDocumentor\Guides\Nodes\Inline\CrossReferenceNode;
use phpDocumentor\Guides\Nodes\Inline\ReferenceNode;
use phpDocumentor\Guides\Nodes\Node;
use phpDocumentor\Guides\Nodes\PrefixedLinkTargetNode;
use T3Docs\Typo3DocsTheme\Inventory\Typo3VersionService;

use function assert;

/** @implements NodeTransformer<CrossReferenceNode> */
final class RedirectsNodeTransformer implements NodeTransformer
{
    public function __construct(
        private readonly Typo3VersionService $typo3VersionService
    ) {}

    public function enterNode(Node $node, CompilerContextInterface $compilerContext): Node
    {
        return $node;
    }

    public function leaveNode(Node $node, CompilerContextInterface $compilerContext): Node
    {
        assert($node instanceof CrossReferenceNode);
        if ($node->getInterlinkDomain() === '') {
            return $node;
        }
        if ($node->getInterlinkDomain() === 't3ts45') {
            $version = $this->typo3VersionService->getPreferredVersion();
            if (!in_array($version, ['12.4', '13.4', 'main'], true)) {
                return $node;
            }
            $prefix = '';
            if ($node instanceof PrefixedLinkTargetNode) {
                $prefix = $node->getPrefix();
            }
            assert(is_string($node->getValue()));
            return new ReferenceNode('guide-' . $node->getTargetReference(), $node->getValue(), $node->getInterlinkDomain(), $node->getInterlinkGroup(), $prefix);
        }
        return $node;
    }

    public function supports(Node $node): bool
    {
        return $node instanceof CrossReferenceNode;
    }

    public function getPriority(): int
    {
        return 900;
    }
}
