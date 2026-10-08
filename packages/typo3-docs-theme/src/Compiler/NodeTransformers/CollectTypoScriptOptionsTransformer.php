<?php

declare(strict_types=1);

namespace T3Docs\Typo3DocsTheme\Compiler\NodeTransformers;

use phpDocumentor\Guides\Compiler\CompilerContextInterface;
use phpDocumentor\Guides\Compiler\NodeTransformer;
use phpDocumentor\Guides\Nodes\Node;
use phpDocumentor\Guides\ReferenceResolvers\AnchorNormalizer;
use phpDocumentor\Guides\RestructuredText\Nodes\ConfvalNode;
use T3Docs\Typo3DocsTheme\ConfvalIndex\ConfvalIndex;
use T3Docs\Typo3DocsTheme\Permalinks\Permalinks;
use T3Docs\Typo3DocsTheme\TypoScriptReference\ExternalTypoScript;
use T3Docs\Typo3DocsTheme\TypoScriptReference\LocalTypoScript;
use T3Docs\Typo3DocsTheme\TypoScriptReference\OptionPaths;

use function in_array;

/**
 * Notes every option the manual documents, so that a ":typoscript:" or
 * ":tsconfig:" role on any page can say what it is: a TypoScript or TSconfig
 * option by its path, any other by the name a role gives in angle brackets.
 * @see DescribeTypoScriptTransformer
 *
 * @implements NodeTransformer<ConfvalNode>
 */
final class CollectTypoScriptOptionsTransformer implements NodeTransformer
{
    public function __construct(
        private readonly LocalTypoScript $localTypoScript,
        private readonly ConfvalIndex $confvalIndex,
        private readonly Permalinks $permalinks,
        private readonly AnchorNormalizer $anchorNormalizer,
    ) {}

    public function enterNode(Node $node, CompilerContextInterface $compilerContext): Node
    {
        if (!$node instanceof ConfvalNode || $node->isNoindex()) {
            return $node;
        }

        // Every option, so that a role can name any by its confval; only
        // TypoScript and TSconfig ones are found by a role by themselves.
        $description = $this->confvalIndex->describe($node);
        $anchor = $this->anchorNormalizer->reduceAnchor($node->getAnchor());
        $this->localTypoScript->addOption($anchor, [
            'name' => $node->getPlainContent(),
            'type' => $description['type'],
            'summary' => $description['summary'],
            'url' => $this->permalinks->forAnchor($anchor, $compilerContext->getProjectNode()->getVersion()),
        ], OptionPaths::declaredIn($description['fields']), in_array($description['searchFacet'], ExternalTypoScript::SEARCH_FACETS, true));

        return $node;
    }

    public function leaveNode(Node $node, CompilerContextInterface $compilerContext): Node
    {
        return $node;
    }

    public function supports(Node $node): bool
    {
        return $node instanceof ConfvalNode;
    }

    public function getPriority(): int
    {
        // Before DescribeTypoScriptTransformer
        return 4000;
    }
}
