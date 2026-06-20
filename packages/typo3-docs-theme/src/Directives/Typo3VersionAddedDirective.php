<?php

declare(strict_types=1);

namespace T3Docs\Typo3DocsTheme\Directives;

use phpDocumentor\Guides\Nodes\CollectionNode;
use phpDocumentor\Guides\RestructuredText\Parser\Productions\Rule;

final class Typo3VersionAddedDirective extends AbstractTypo3VersionChangeDirective
{
    /** @param Rule<CollectionNode> $startingRule */
    public function __construct(Rule $startingRule)
    {
        parent::__construct($startingRule, 'versionadded', 'New in version %s');
    }
}
