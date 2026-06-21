<?php

declare(strict_types=1);

namespace T3Docs\Typo3DocsTheme\Directives;

use phpDocumentor\Guides\Nodes\CollectionNode;
use phpDocumentor\Guides\RestructuredText\Parser\Productions\Rule;
use T3Docs\Typo3DocsTheme\Settings\Typo3DocsThemeSettings;

final class Typo3VersionChangedDirective extends AbstractTypo3VersionChangeDirective
{
    /** @param Rule<CollectionNode> $startingRule */
    public function __construct(Rule $startingRule, Typo3DocsThemeSettings $themeSettings)
    {
        parent::__construct($startingRule, 'versionchanged', 'Changed in version %s', $themeSettings);
    }
}
