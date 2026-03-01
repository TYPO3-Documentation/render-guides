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

namespace T3Docs\Typo3DocsTheme\Directives;

use phpDocumentor\Guides\Nodes\CollectionNode;
use phpDocumentor\Guides\Nodes\InlineCompoundNode;
use phpDocumentor\Guides\Nodes\Node;
use phpDocumentor\Guides\ReferenceResolvers\AnchorNormalizer;
use phpDocumentor\Guides\RestructuredText\Directives\SubDirective;
use phpDocumentor\Guides\RestructuredText\Parser\BlockContext;
use phpDocumentor\Guides\RestructuredText\Parser\Directive;
use phpDocumentor\Guides\RestructuredText\Parser\Productions\Rule;
use T3Docs\Typo3DocsTheme\Nodes\GroupTabNode;

class GroupTabDirective extends SubDirective
{
    public function __construct(
        Rule                              $startingRule,
        private readonly AnchorNormalizer $anchorNormalizer,
    ) {
        parent::__construct($startingRule);
    }
    public function getName(): string
    {
        return 'group-tab';
    }

    protected function processSub(
        BlockContext $blockContext,
        CollectionNode $collectionNode,
        Directive $directive,
    ): Node|null {
        $key = $this->anchorNormalizer->reduceAnchor($directive->getData());
        return new GroupTabNode(
            'group-tab',
            $directive->getData(),
            $directive->getDataNode() ?? new InlineCompoundNode(),
            $key,
            array_values($collectionNode->getChildren()),
        );
    }
}
