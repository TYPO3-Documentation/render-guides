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
use phpDocumentor\Guides\Nodes\FieldListNode;
use phpDocumentor\Guides\Nodes\ListNode;
use phpDocumentor\Guides\Nodes\Node;
use phpDocumentor\Guides\Nodes\Table\TableColumn;
use phpDocumentor\Guides\Nodes\Table\TableRow;
use phpDocumentor\Guides\Nodes\TableNode;
use phpDocumentor\Guides\RestructuredText\Directives\SubDirective;
use phpDocumentor\Guides\RestructuredText\Parser\BlockContext;
use phpDocumentor\Guides\RestructuredText\Parser\Directive;
use phpDocumentor\Guides\RestructuredText\Parser\Productions\Rule;
use Psr\Log\LoggerInterface;

class T3FieldListTableDirective extends SubDirective
{
    /** @param Rule<CollectionNode> $startingRule */
    public function __construct(
        protected Rule $startingRule,
        protected LoggerInterface $logger,
    ) {
        parent::__construct($startingRule);
    }

    public function getName(): string
    {
        return 't3-field-list-table';
    }

    protected function processSub(
        BlockContext $blockContext,
        CollectionNode $collectionNode,
        Directive $directive,
    ): Node|null {
        $i = 0;
        $headers = [];
        $rows = [];
        foreach ($collectionNode->getChildren() as $list) {
            if (!$list instanceof ListNode) {
                $this->logger->warning(sprintf('Only lists are allowed in a t3-field list. Node of type %s found.', $list::class), $blockContext->getLoggerInformation());
                continue;
            }
            foreach ($list->getChildren() as $listItem) {
                if ($i === 0) {
                    $header = new TableRow();
                    foreach ($listItem->getChildren() as $fieldlist) {
                        if (!$fieldlist instanceof FieldListNode) {
                            $this->logger->warning(sprintf('Only field lists are allowed in each list item a t3-field list. Node of type %s found.', $list::class), $blockContext->getLoggerInformation());
                            continue;
                        }
                        foreach ($fieldlist->getChildren() as $fieldlistItem) {
                            $columnNode = new TableColumn($fieldlistItem->getTerm(), 1, $fieldlistItem->getChildren());
                            $header->addColumn($columnNode);
                        }
                    }
                    $headers[] = $header;
                    $i++;
                    continue;
                }
                $row = new TableRow();
                foreach ($listItem->getChildren() as $fieldlist) {
                    if (!$fieldlist instanceof FieldListNode) {
                        $this->logger->warning(sprintf('Only field lists are allowed in each list item a t3-field list. Node of type %s found.', $list::class), $blockContext->getLoggerInformation());
                        continue;
                    }
                    foreach ($fieldlist->getChildren() as $fieldlistItem) {
                        $columnNode = new TableColumn($fieldlistItem->getTerm(), 1, $fieldlistItem->getChildren());
                        $row->addColumn($columnNode);
                    }
                }
                $rows[] = $row;
                $i++;
            }
        }
        return new TableNode($rows, $headers);
    }
}
