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
use phpDocumentor\Guides\RestructuredText\Nodes\ConfvalNode;
use phpDocumentor\Guides\RestructuredText\Parser\BlockContext;
use phpDocumentor\Guides\RestructuredText\Parser\Directive;
use phpDocumentor\Guides\RestructuredText\Parser\Productions\Rule;
use Psr\Log\LoggerInterface;
use T3Docs\Typo3DocsTheme\Nodes\ConfvalMenuNode;

class ConfvalMenuDirective extends SubDirective
{
    public function __construct(
        Rule $startingRule,
        private readonly LoggerInterface $logger,
        private readonly AnchorNormalizer $anchorReducer,
    ) {
        parent::__construct($startingRule);
    }
    protected function processSub(
        BlockContext   $blockContext,
        CollectionNode $collectionNode,
        Directive      $directive,
    ): Node|null {
        $originalChildren = $collectionNode->getChildren();
        $chilConfvals = [];
        foreach ($originalChildren as $child) {
            if ($child instanceof ConfvalNode) {
                $chilConfvals[] = $child;
            } else {
                $this->logger->warning('A confval-menu may only contain confvals. ', $blockContext->getLoggerInformation());
            }
        }
        $fields = [];
        $display = 'list';
        $excludeNoindex = false;
        $excludeString = '';
        foreach ($directive->getOptions() as $option) {
            if ($option->getName() == 'name' || $option->getName() == 'class') {
                continue;
            }
            if ($option->getName() == 'display') {
                $display = (string) $option->getValue();
                continue;
            }
            if ($option->getName() == 'exclude-noindex') {
                $excludeNoindex = (bool) $option->getValue();
                continue;
            }
            if ($option->getName() == 'exclude') {
                $excludeString = (string) $option->getValue();
                continue;
            }
            $fields[] = $option->getName();
        }
        $exclude = explode(',', $excludeString);
        $anchorReducer = $this->anchorReducer;
        $exclude = array_map(function ($element) use ($anchorReducer) {
            return $anchorReducer->reduceAnchor($element);
        }, $exclude);
        return new ConfvalMenuNode(
            $directive->getData(),
            $directive->getDataNode() ?? new InlineCompoundNode(),
            $chilConfvals,
            $chilConfvals,
            $fields,
            $display,
            $excludeNoindex,
            $exclude,
        );
    }
    public function getName(): string
    {
        return 'confval-menu';
    }
}
