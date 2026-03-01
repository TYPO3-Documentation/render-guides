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

namespace T3Docs\Typo3DocsTheme\Compiler\NodeTransformers;

use phpDocumentor\Guides\Compiler\CompilerContextInterface;
use phpDocumentor\Guides\Compiler\NodeTransformer;
use phpDocumentor\Guides\Nodes\DocumentNode;
use phpDocumentor\Guides\Nodes\Node;
use phpDocumentor\Guides\Nodes\SectionNode;
use T3Docs\Typo3DocsTheme\Nodes\Typo3TalkNode;

/** @implements NodeTransformer<DocumentNode|SectionNode|Typo3TalkNode> */
final class Typo3TalkNodeTransformer implements NodeTransformer
{
    /** @var SectionNode[] $sectionStack */
    private array $sectionStack = [];

    public function __construct(
    ) {}

    public function enterNode(Node $node, CompilerContextInterface $compilerContext): Node
    {
        if ($node instanceof DocumentNode) {
            $this->sectionStack = [];
            return $node;
        }
        if ($node instanceof SectionNode) {
            $this->sectionStack[] = $node;
            return $node;
        }
        if ($node instanceof Typo3TalkNode && $this->sectionStack !== []) {
            $currentSection = end($this->sectionStack);
            $node->setSectionNode($currentSection);
        }
        return $node;
    }

    public function leaveNode(Node $node, CompilerContextInterface $compilerContext): Node
    {
        if ($node instanceof DocumentNode) {
            $this->sectionStack = [];
            return $node;
        }
        if ($node instanceof SectionNode) {
            array_pop($this->sectionStack);
            return $node;
        }
        return $node;
    }

    public function supports(Node $node): bool
    {
        return $node instanceof DocumentNode || $node instanceof SectionNode || $node instanceof Typo3TalkNode;
    }

    public function getPriority(): int
    {
        return 100000;
    }
}
