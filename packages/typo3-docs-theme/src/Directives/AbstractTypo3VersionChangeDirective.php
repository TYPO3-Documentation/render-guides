<?php

declare(strict_types=1);

namespace T3Docs\Typo3DocsTheme\Directives;

use phpDocumentor\Guides\Nodes\CollectionNode;
use phpDocumentor\Guides\Nodes\Node;
use phpDocumentor\Guides\RestructuredText\Directives\SubDirective;
use phpDocumentor\Guides\RestructuredText\Parser\BlockContext;
use phpDocumentor\Guides\RestructuredText\Parser\Directive;
use phpDocumentor\Guides\RestructuredText\Parser\Productions\Rule;
use T3Docs\Typo3DocsTheme\Nodes\Typo3VersionChangeNode;

use function array_values;
use function str_contains;
use function trim;

/**
 * TYPO3 specific version of phpDocumentor's version change directives
 * (versionadded, versionchanged, deprecated).
 *
 * In addition to the default behaviour it supports a ":changelog:" option that
 * renders a link to the related TYPO3 changelog entry, e.g.:
 *
 * ..  versionchanged:: 14.0
 *     :changelog: feature-107628-1729026000
 *
 *     This module has been moved from :guilabel:`System` to
 *     :guilabel:`Administration`.
 *
 * The option value is a changelog entry identifier (resolved against the
 * "changelog" interlink inventory via the docs.typo3.org permalink service). A
 * fully qualified interlink target such as "changelog:feature-..." is accepted
 * as well.
 */
abstract class AbstractTypo3VersionChangeDirective extends SubDirective
{
    private const PERMALINK_BASE = 'https://docs.typo3.org/permalink/';
    private const CHANGELOG_INVENTORY = 'changelog';

    /** @param Rule<CollectionNode> $startingRule */
    public function __construct(
        Rule $startingRule,
        private readonly string $type,
        private readonly string $label,
    ) {
        parent::__construct($startingRule);
    }

    final public function getName(): string
    {
        return $this->type;
    }

    final protected function processSub(
        BlockContext $blockContext,
        CollectionNode $collectionNode,
        Directive $directive,
    ): Node {
        return new Typo3VersionChangeNode(
            $this->type,
            $this->label,
            $directive->getData(),
            array_values($collectionNode->getChildren()),
            $this->buildChangelogUrl($directive->getOptionString('changelog')),
        );
    }

    private function buildChangelogUrl(string $changelog): string|null
    {
        $changelog = trim($changelog);
        if ($changelog === '') {
            return null;
        }

        $target = str_contains($changelog, ':')
            ? $changelog
            : self::CHANGELOG_INVENTORY . ':' . $changelog;

        return self::PERMALINK_BASE . $target;
    }
}
