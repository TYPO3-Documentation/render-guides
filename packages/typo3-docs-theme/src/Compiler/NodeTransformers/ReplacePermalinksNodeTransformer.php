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
use phpDocumentor\Guides\Nodes\Inline\HyperLinkNode;
use phpDocumentor\Guides\Nodes\Inline\ReferenceNode;
use phpDocumentor\Guides\Nodes\Node;

use function assert;

/** @implements NodeTransformer<HyperLinkNode|ReferenceNode> */
final class ReplacePermalinksNodeTransformer implements NodeTransformer
{
    public function __construct(
    ) {}

    public function enterNode(Node $node, CompilerContextInterface $compilerContext): Node
    {
        return $node;
    }

    public function leaveNode(Node $node, CompilerContextInterface $compilerContext): Node
    {
        assert($node instanceof HyperLinkNode);
        if (!str_starts_with($node->getTargetReference(), 'https://docs.typo3.org/permalink/')) {
            return $node;
        }
        // When the link's visible label equals its URL (auto-linkified plain URL),
        // drop the children so the resolver fills in the canonical title.
        $children = $node->toString() === $node->getTargetReference() ? [] : $node->getChildren();
        $url = str_replace('https://docs.typo3.org/permalink/', '', ($node->getTargetReference()));
        $version = null;
        $interlink = null;
        if (str_contains($url, '@')) {
            [$url, $version] = explode('@', $url, 2);
        }
        if (str_contains($url, ':')) {
            [$interlink, $url] = explode(':', $url, 2);
        }
        if ($version !== null && $interlink !== null) {
            $interlink = $interlink . '/' . $version;
        }
        $node = new ReferenceNode($url, $children, $interlink ?? '');
        return $node;
    }

    public function supports(Node $node): bool
    {
        return $node instanceof HyperLinkNode;
    }

    public function getPriority(): int
    {
        return 1000;
    }
}
