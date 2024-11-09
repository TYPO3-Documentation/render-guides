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
use phpDocumentor\Guides\Nodes\DefinitionListNode;
use phpDocumentor\Guides\Nodes\DefinitionLists\DefinitionListItemNode;
use phpDocumentor\Guides\Nodes\InlineCompoundNode;
use phpDocumentor\Guides\Nodes\Node;
use phpDocumentor\Guides\RestructuredText\Directives\SubDirective;
use phpDocumentor\Guides\RestructuredText\Parser\BlockContext;
use phpDocumentor\Guides\RestructuredText\Parser\Directive;
use phpDocumentor\Guides\RestructuredText\Parser\Productions\Rule;
use T3Docs\Typo3DocsTheme\Nodes\GlossaryNode;

class GlossaryDirective extends SubDirective
{
    public const NAME = 'glossary';
    public function __construct(
        Rule $startingRule
    ) {
        parent::__construct($startingRule);
    }
    protected function processSub(
        BlockContext   $blockContext,
        CollectionNode $collectionNode,
        Directive      $directive,
    ): Node|null {
        $originalChildren = $collectionNode->getChildren();
        $entries = [];
        foreach ($originalChildren as $node) {
            if (!$node instanceof DefinitionListNode) {
                continue;
            }
            foreach ($node->getChildren() as $item) {
                if (!$item instanceof DefinitionListItemNode) {
                    continue;
                }
                $term = $item->getTerm()->toString();
                $firstChar = $term[0];

                if (ctype_alpha($firstChar)) {
                    $firstChar = strtoupper($firstChar);
                } else {
                    $firstChar = '*';
                }
                $entries[$firstChar] ??= [];
                $entries[$firstChar][$term] = $item;
            }
        }
        uksort($entries, 'strcasecmp');
        foreach ($entries as &$terms) {
            uksort($terms, 'strcasecmp');
        }
        unset($terms);
        return new GlossaryNode(
            $directive->getData(),
            $directive->getDataNode() ?? new InlineCompoundNode(),
            $originalChildren,
            $entries,
        );
    }
    public function getName(): string
    {
        return self::NAME;
    }
}
