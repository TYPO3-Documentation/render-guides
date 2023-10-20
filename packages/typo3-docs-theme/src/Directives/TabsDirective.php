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

use phpDocumentor\Guides\Nodes\DocumentNode;
use phpDocumentor\Guides\Nodes\InlineCompoundNode;
use phpDocumentor\Guides\Nodes\Node;
use phpDocumentor\Guides\RestructuredText\Directives\SubDirective;
use phpDocumentor\Guides\Nodes\CollectionNode;
use phpDocumentor\Guides\RestructuredText\Parser\Directive;
use T3Docs\Typo3DocsTheme\Nodes\ConfvalNode;
use T3Docs\Typo3DocsTheme\Nodes\GroupTabNode;
use T3Docs\Typo3DocsTheme\Nodes\TabsNode;

/**
 */
class TabsDirective extends SubDirective
{
    public function getName(): string
    {
        return 'tabs';
    }

    /** {@inheritDoc}
     *
     * @param Directive $directive
     */
    protected function processSub(
        CollectionNode $collectionNode,
        Directive $directive,
    ): Node|null {
        return new TabsNode(
            'group-tab',
            $directive->getData(),
            $directive->getDataNode() ?? new InlineCompoundNode(),
            $collectionNode->getChildren()
        );
    }
}
