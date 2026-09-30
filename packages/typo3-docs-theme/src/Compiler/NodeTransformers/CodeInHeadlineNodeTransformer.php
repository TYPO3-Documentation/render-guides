<?php

declare(strict_types=1);

namespace T3Docs\Typo3DocsTheme\Compiler\NodeTransformers;

use phpDocumentor\Guides\Compiler\CompilerContextInterface;
use phpDocumentor\Guides\Compiler\NodeTransformer;
use phpDocumentor\Guides\Nodes\CompoundNode;
use phpDocumentor\Guides\Nodes\Node;
use phpDocumentor\Guides\Nodes\TitleNode;
use T3Docs\Typo3DocsTheme\Nodes\Inline\InHeadline;

/**
 * Marks the code roles in a headline, so that they render as plain inline
 * code there.
 *
 * In body text a code role such as ":php:" or ":typoscript:" opens an infobox,
 * and ":file:" and ":composer:" open a popup. In a headline that button sits
 * inside the heading, a link target of its own, and nobody expects to click
 * there. The headline shows the text the way a literal in backticks shows it;
 * the role is still there, so the class index lists a class that a headline
 * names.
 *
 * Where a headline is reused as text -- the menu, the page title, a reference
 * without a link text -- a code role was plain text already.
 *
 * @implements NodeTransformer<TitleNode>
 */
final class CodeInHeadlineNodeTransformer implements NodeTransformer
{
    public function enterNode(Node $node, CompilerContextInterface $compilerContext): Node
    {
        if ($node instanceof TitleNode) {
            $this->mark($node);
        }

        return $node;
    }

    private function mark(Node $node): void
    {
        if ($node instanceof InHeadline) {
            $node->markInHeadline();
            return;
        }
        if (!$node instanceof CompoundNode) {
            return;
        }
        foreach ($node->getChildren() as $child) {
            if ($child instanceof Node) {
                $this->mark($child);
            }
        }
    }

    public function leaveNode(Node $node, CompilerContextInterface $compilerContext): Node
    {
        return $node;
    }

    public function supports(Node $node): bool
    {
        return $node instanceof TitleNode;
    }

    public function getPriority(): int
    {
        return 1000;
    }
}
