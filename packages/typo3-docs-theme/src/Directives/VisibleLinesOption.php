<?php

declare(strict_types=1);

namespace T3Docs\Typo3DocsTheme\Directives;

use phpDocumentor\Guides\Nodes\CodeNode;
use phpDocumentor\Guides\RestructuredText\Parser\BlockContext;
use phpDocumentor\Guides\RestructuredText\Parser\Directive;
use Psr\Log\LoggerInterface;
use T3Docs\Typo3DocsTheme\CodeFolding\FoldedLines;

use function sprintf;

/**
 * Carries ":visible-lines:" from a code-block or literalinclude to its node.
 *
 * A value that is neither "all" nor a list of lines is reported and dropped,
 * which leaves the block with the default folding.
 */
final class VisibleLinesOption
{
    public function __construct(
        private readonly LoggerInterface $logger,
    ) {}

    public function apply(CodeNode $node, Directive $directive, BlockContext $blockContext): CodeNode
    {
        if (!$directive->hasOption(FoldedLines::OPTION)) {
            return $node;
        }

        $value = $directive->getOptionString(FoldedLines::OPTION);
        if (!FoldedLines::isValid($value)) {
            $this->logger->warning(
                sprintf(
                    'Option `:%s: %s` is not a list of lines such as "12-20" or "3-5, 12-20", nor "all". It is ignored.',
                    FoldedLines::OPTION,
                    $value,
                ),
                $blockContext->getLoggerInformation(),
            );

            return $node;
        }

        $result = $node->withOptions([FoldedLines::OPTION => $value]);

        return $result instanceof CodeNode ? $result : $node;
    }
}
