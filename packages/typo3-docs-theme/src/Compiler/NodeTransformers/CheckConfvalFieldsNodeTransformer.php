<?php

declare(strict_types=1);

namespace T3Docs\Typo3DocsTheme\Compiler\NodeTransformers;

use phpDocumentor\Guides\Compiler\CompilerContextInterface;
use phpDocumentor\Guides\Compiler\NodeTransformer;
use phpDocumentor\Guides\Nodes\Node;
use phpDocumentor\Guides\RestructuredText\Nodes\ConfvalNode;
use Psr\Log\LoggerInterface;
use T3Docs\Typo3DocsTheme\ConfvalFields\ConfvalFields;
use T3Docs\Typo3DocsTheme\Directives\SiteSetSettingsDirective;

use function in_array;
use function sprintf;

/**
 * Warns about a field on ".. confval::" that the manual does not declare.
 * @see ConfvalFields
 *
 * A site setting is a confval the theme writes itself, from the settings
 * definition of a site set, with fields of its own that nobody types: it is
 * not checked.
 *
 * @implements NodeTransformer<ConfvalNode>
 */
final class CheckConfvalFieldsNodeTransformer implements NodeTransformer
{
    public function __construct(
        private readonly ConfvalFields $confvalFields,
        private readonly LoggerInterface $logger,
    ) {}

    public function enterNode(Node $node, CompilerContextInterface $compilerContext): Node
    {
        if (!$node instanceof ConfvalNode || !$this->confvalFields->isChecked() || $this->isSiteSetting($node)) {
            return $node;
        }

        foreach ($node->getAdditionalOptions() as $field => $value) {
            if ($this->confvalFields->isAllowed($field)) {
                continue;
            }
            $this->logger->warning(sprintf(
                'The confval "%s" has the field "%s", which the manual does not declare. %s',
                $node->getPlainContent(),
                $field,
                $this->confvalFields->advice($field),
            ), $compilerContext->getLoggerInformation());
        }

        return $node;
    }

    private function isSiteSetting(ConfvalNode $node): bool
    {
        $facet = $node->getAdditionalOptions()['searchFacet'] ?? null;

        return $facet !== null && in_array(
            $facet->toString(),
            [SiteSetSettingsDirective::FACET, SiteSetSettingsDirective::CATEGORY_FACET],
            true,
        );
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
        return 1000;
    }
}
