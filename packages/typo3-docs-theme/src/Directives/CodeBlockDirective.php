<?php

declare(strict_types=1);

namespace T3Docs\Typo3DocsTheme\Directives;

use phpDocumentor\Guides\Nodes\CodeNode;
use phpDocumentor\Guides\Nodes\Node;
use phpDocumentor\Guides\RestructuredText\Directives\BaseDirective;
use phpDocumentor\Guides\RestructuredText\Directives\CodeBlockDirective as GuidesCodeBlockDirective;
use phpDocumentor\Guides\RestructuredText\Parser\BlockContext;
use phpDocumentor\Guides\RestructuredText\Parser\Directive;

/**
 * The library's code-block, plus ":visible-lines:".
 *
 * The library's directive drops every option it does not map itself, so the
 * option is read here and put on the node it returns. @see VisibleLinesOption
 */
final class CodeBlockDirective extends BaseDirective
{
    public function __construct(
        private readonly GuidesCodeBlockDirective $inner,
        private readonly VisibleLinesOption $visibleLines,
    ) {}

    public function getName(): string
    {
        return $this->inner->getName();
    }

    /** @return string[] */
    public function getAliases(): array
    {
        return $this->inner->getAliases();
    }

    public function process(BlockContext $blockContext, Directive $directive): Node|null
    {
        $node = $this->inner->process($blockContext, $directive);

        return $node instanceof CodeNode ? $this->visibleLines->apply($node, $directive, $blockContext) : $node;
    }
}
