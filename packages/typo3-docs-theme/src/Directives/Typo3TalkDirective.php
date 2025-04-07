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

use phpDocumentor\Guides\Nodes\Node;
use phpDocumentor\Guides\RestructuredText\Directives\BaseDirective;
use phpDocumentor\Guides\RestructuredText\Parser\BlockContext;
use phpDocumentor\Guides\RestructuredText\Parser\Directive;
use T3Docs\Typo3DocsTheme\Nodes\Typo3TalkNode;

class Typo3TalkDirective extends BaseDirective
{
    public function __construct() {}

    public function getName(): string
    {
        return 'typo3:talk';
    }

    public function processNode(
        BlockContext $blockContext,
        Directive $directive,
    ): Node {
        return new Typo3TalkNode();
    }
}
