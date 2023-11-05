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

use phpDocumentor\Guides\Nodes\InlineCompoundNode;
use phpDocumentor\Guides\Nodes\Node;
use phpDocumentor\Guides\Nodes\ParagraphNode;
use phpDocumentor\Guides\RestructuredText\Directives\BaseDirective;
use phpDocumentor\Guides\RestructuredText\Parser\BlockContext;
use phpDocumentor\Guides\RestructuredText\Parser\Directive;
use Psr\Log\LoggerInterface;
use T3Docs\Typo3DocsTheme\Nodes\YoutubeNode;

class YoutubeDirective extends BaseDirective
{
    public function __construct(
        private readonly LoggerInterface $logger
    ) {}

    public function getName(): string
    {
        return 'youtube';
    }

    public function processNode(
        BlockContext $blockContext,
        Directive $directive,
    ): Node {
        $videoId = trim($directive->getData());
        if (!preg_match('/^[a-zA-Z0-9_-]{11}$/', $videoId)) {
            $this->logger->warning(sprintf('The following youtube id is not valid: %s', $videoId), $blockContext->getLoggerInformation());
            return new ParagraphNode([InlineCompoundNode::getPlainTextInlineNode('Video cannot be displayed')]);
        }
        return new YoutubeNode($videoId);
    }
}
