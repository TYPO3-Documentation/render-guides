<?php

declare(strict_types=1);

namespace T3Docs\Typo3DocsTheme\Directives;

use phpDocumentor\Guides\Nodes\CollectionNode;
use phpDocumentor\Guides\RestructuredText\Parser\Productions\Rule;
use Psr\Log\LoggerInterface;
use T3Docs\Typo3DocsTheme\Settings\Typo3DocsThemeSettings;

final class Typo3DeprecatedDirective extends AbstractTypo3VersionChangeDirective
{
    /** @param Rule<CollectionNode> $startingRule */
    public function __construct(Rule $startingRule, Typo3DocsThemeSettings $themeSettings, LoggerInterface $logger)
    {
        parent::__construct($startingRule, 'deprecated', 'Deprecated since version %s', $themeSettings, $logger);
    }
}
