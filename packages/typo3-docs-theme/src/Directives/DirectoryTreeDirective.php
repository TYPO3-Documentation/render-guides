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
use phpDocumentor\Guides\Nodes\ListItemNode;
use phpDocumentor\Guides\Nodes\ListNode;
use phpDocumentor\Guides\Nodes\Node;
use phpDocumentor\Guides\RestructuredText\Directives\SubDirective;
use phpDocumentor\Guides\RestructuredText\Parser\BlockContext;
use phpDocumentor\Guides\RestructuredText\Parser\Directive;
use phpDocumentor\Guides\RestructuredText\Parser\Productions\Rule;
use Psr\Log\LoggerInterface;
use T3Docs\Typo3DocsTheme\Nodes\DirectoryTree\DirectoryTreeListItemNode;
use T3Docs\Typo3DocsTheme\Nodes\DirectoryTree\DirectoryTreeListNode;
use T3Docs\Typo3DocsTheme\Nodes\DirectoryTreeNode;

class DirectoryTreeDirective extends SubDirective
{
    private static int $counter = 0;
    public function __construct(
        Rule $startingRule,
        private readonly LoggerInterface $logger,
    ) {
        parent::__construct($startingRule);
    }

    public function getName(): string
    {
        return 'directory-tree';
    }

    protected function processSub(
        BlockContext   $blockContext,
        CollectionNode $collectionNode,
        Directive      $directive,
    ): Node|null {
        if($directive->hasOption('name')) {
            $id = $directive->getOption('name')->toString();
        } else {
            self::$counter++;
            $id = 'directory-tree-' . self::$counter;
        }
        if($directive->hasOption('level')) {
            $level = (int) $directive->getOption('level')->getValue();
        } else {
            $level = PHP_INT_MAX;
        }
        $showFileIcons = false;
        if ($directive->hasOption('show-file-icons')) {
            $showFileIcons = (bool) $directive->getOption('show-file-icons')->getValue();
        }
        $originalChildren = $collectionNode->getChildren();
        $children = [];
        foreach ($originalChildren as $child) {
            if ($child instanceof ListNode) {
                $children[] = $this->setNames($child, $id);
            } else {
                $this->logger->warning('A directory-tree may only a list. ', $blockContext->getLoggerInformation());
            }
        }
        if (count($children) === 0) {
            $this->logger->warning('A directory-tree must contain at least one list. ', $blockContext->getLoggerInformation());
        }
        return new DirectoryTreeNode(
            'directory-tree',
            $directive->getData(),
            $directive->getDataNode() ?? new InlineCompoundNode(),
            $children,
            $id,
            $level,
            $showFileIcons,
        );
    }

    private function setNames(ListNode $list, string $id, int $count = 0): DirectoryTreeListNode
    {
        $count++;
        if (!$list->hasOption('name')) {
            $list = $list->withOptions(['name' => $id]);
        }
        $children = [];
        $subCount = 0;
        foreach ($list->getChildren() as $child) {
            if ($child instanceof ListItemNode) {
                $subCount++;
                $children[] = $this->setItemNames($child, $id, $subCount);
            } else {
                $children[] = $child;
            }
        }
        return new DirectoryTreeListNode($children, $id);
    }
    private function setItemNames(ListItemNode $item, string $id, int $count): DirectoryTreeListItemNode
    {
        $id .= '-' . $count;
        $children = [];
        $lists = [];
        $subCount = 0;
        foreach ($item->getChildren() as $child) {
            if ($child instanceof ListNode) {
                $subCount++;
                $lists[] = $this->setNames($child, $id, $subCount);
            } else {
                $children[] = $child;
            }
        }
        return new DirectoryTreeListItemNode($children, $id, $lists);
    }
}
