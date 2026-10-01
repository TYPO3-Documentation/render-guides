<?php

declare(strict_types=1);

namespace T3Docs\Typo3DocsTheme\Compiler\NodeTransformers;

use phpDocumentor\Guides\Compiler\CompilerContextInterface;
use phpDocumentor\Guides\Compiler\NodeTransformer;
use phpDocumentor\Guides\Nodes\Node;
use phpDocumentor\Guides\ReferenceResolvers\AnchorNormalizer;
use T3Docs\Typo3DocsTheme\Nodes\ViewHelperNode;
use T3Docs\Typo3DocsTheme\Permalinks\Permalinks;
use T3Docs\Typo3DocsTheme\ViewHelperIndex\LocalViewHelpers;
use T3Docs\Typo3DocsTheme\ViewHelperIndex\ViewHelperIndex;

/**
 * Notes every ViewHelper the manual documents, so that a ":fluid:" role on
 * any page can say what it does. @see DescribeFluidViewHelpersTransformer
 *
 * The link is the ViewHelper's permalink: the infobox needs an address of its
 * own, and the permalink is one that does not depend on the page the role is
 * on. A manual without permalinks gives no link, only the summary.
 *
 * @implements NodeTransformer<ViewHelperNode>
 */
final class CollectViewHelpersTransformer implements NodeTransformer
{
    public function __construct(
        private readonly LocalViewHelpers $localViewHelpers,
        private readonly ViewHelperIndex $viewHelperIndex,
        private readonly Permalinks $permalinks,
        private readonly AnchorNormalizer $anchorNormalizer,
    ) {}

    public function enterNode(Node $node, CompilerContextInterface $compilerContext): Node
    {
        if ($node instanceof ViewHelperNode && !$node->isNoindex()) {
            $this->localViewHelpers->add($node->getFullName(), [
                'summary' => $this->viewHelperIndex->summary($node),
                'url' => $this->permalinks->forAnchor(
                    $this->anchorNormalizer->reduceAnchor($node->getAnchor()),
                    $compilerContext->getProjectNode()->getVersion(),
                ),
            ]);
        }

        return $node;
    }

    public function leaveNode(Node $node, CompilerContextInterface $compilerContext): Node
    {
        return $node;
    }

    public function supports(Node $node): bool
    {
        return $node instanceof ViewHelperNode;
    }

    public function getPriority(): int
    {
        // Before DescribeFluidViewHelpersTransformer
        return 4000;
    }
}
