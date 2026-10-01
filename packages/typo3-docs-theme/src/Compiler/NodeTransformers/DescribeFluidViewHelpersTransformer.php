<?php

declare(strict_types=1);

namespace T3Docs\Typo3DocsTheme\Compiler\NodeTransformers;

use phpDocumentor\Guides\Compiler\CompilerContextInterface;
use phpDocumentor\Guides\Compiler\NodeTransformer;
use phpDocumentor\Guides\Nodes\Node;
use T3Docs\Typo3DocsTheme\Nodes\Inline\CodeInlineNode;
use T3Docs\Typo3DocsTheme\TextRoles\FluidTextTextRole;
use T3Docs\Typo3DocsTheme\ViewHelperIndex\ExternalViewHelpers;
use T3Docs\Typo3DocsTheme\ViewHelperIndex\LocalViewHelpers;

/**
 * Tells a ":fluid:" role that names a ViewHelper what the ViewHelper does and
 * where it is documented, as ":php:" does for a class.
 *
 * A ViewHelper the manual documents itself is described from its own pages,
 * which is what the Fluid ViewHelper Reference does; any other from the
 * reference's "viewhelpers.json". One neither knows stays Fluid code.
 *
 * @implements NodeTransformer<CodeInlineNode>
 */
final class DescribeFluidViewHelpersTransformer implements NodeTransformer
{
    public function __construct(
        private readonly LocalViewHelpers $localViewHelpers,
        private readonly ExternalViewHelpers $externalViewHelpers,
    ) {}

    public function enterNode(Node $node, CompilerContextInterface $compilerContext): Node
    {
        if (!$node instanceof CodeInlineNode) {
            return $node;
        }
        $name = $node->getInfo()[FluidTextTextRole::VIEW_HELPER_INFO] ?? '';
        if ($name === '') {
            return $node;
        }

        $viewHelper = $this->localViewHelpers->find($name) ?? $this->externalViewHelpers->find($name);
        if ($viewHelper === null) {
            return $node;
        }

        $info = ['signature' => $name];
        if ($viewHelper['url'] !== '') {
            $info['url'] = $viewHelper['url'];
        }
        $node->describeAs('Fluid ViewHelper', $viewHelper['summary'], $info);

        return $node;
    }

    public function leaveNode(Node $node, CompilerContextInterface $compilerContext): Node
    {
        return $node;
    }

    public function supports(Node $node): bool
    {
        return $node instanceof CodeInlineNode;
    }

    public function getPriority(): int
    {
        // After CollectViewHelpersTransformer
        return 2000;
    }
}
