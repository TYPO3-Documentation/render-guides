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
        $rows = [];
        foreach ($collectionNode->getChildren() as $list) {
            if (!$list instanceof ListNode) {
                $this->logger->warning(sprintf('Only lists are allowed in a t3-field list. Node of type %s found.', $list::class), $blockContext->getLoggerInformation());
                continue;
            }
            foreach ($list->getChildren() as $listItem) {
                $row = new TableRow();
                foreach ($listItem->getChildren() as $fieldlist) {
                    if (!$fieldlist instanceof FieldListNode) {
                        $this->logger->warning(sprintf('Only field lists are allowed in each list item a t3-field list. Node of type %s found.', $fieldlist::class), $blockContext->getLoggerInformation());
                        continue;
                    }
                    foreach ($fieldlist->getChildren() as $fieldlistItem) {
                        $columnNode = new TableColumn($fieldlistItem->getTerm(), 1, $fieldlistItem->getChildren());
                        $row->addColumn($columnNode);
                    }
                }
                $rows[] = $row;
            }
        }
        $headers = array_splice($rows, 0, $this->headerRows($blockContext, $directive));
        if ($collectionNode->getChildren() === []) {
            // Nothing was indented under the directive, so it says nothing: the
            // HTML shows an empty table and the Markdown shows nothing at all.
            // Rather than let that pass unremarked, the author is told where.
            // Content that is there but unusable is reported by the two
            // warnings above, and must not be reported a second time here.
            $this->logger->warning('The t3-field-list-table directive has no content. It must contain a list of field lists.', $blockContext->getLoggerInformation());
        }

        $tableNode = new TableNode($rows, $headers);
        return $tableNode;
    }

    /**
     * How many of the first items make the header: as many as ":header-rows:"
     * says, and one without it -- the first item was always the header, and
     * a table written without the option relies on that.
     */
    private function headerRows(BlockContext $blockContext, Directive $directive): int
    {
        if (!$directive->hasOption('header-rows')) {
            return 1;
        }
        $value = trim((string) $directive->getOption('header-rows')->getValue());
        if (preg_match('/^\d+$/', $value) !== 1) {
            $this->logger->warning(
                sprintf('The t3-field-list-table option :header-rows: must be a number of rows, "%s" given. The first row is used as header.', $value),
                $blockContext->getLoggerInformation(),
            );
            return 1;
        }
        return (int) $value;
    }
}
