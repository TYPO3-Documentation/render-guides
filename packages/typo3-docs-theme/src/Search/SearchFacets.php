<?php

declare(strict_types=1);

namespace T3Docs\Typo3DocsTheme\Search;

use T3Docs\Typo3DocsTheme\Directives\SiteSetSettingsDirective;

use function in_array;
use function trim;

/**
 * The kinds of option the search of docs.typo3.org tells apart.
 *
 * A confval names its kind with ":searchFacet:", or takes the manual's
 * "confval-default". The list is one for all manuals, so that the same kind is
 * spelled the same way wherever it is documented. Anything else is an "Option".
 */
final class SearchFacets
{
    public const OTHER = 'Option';

    private const ALLOWED = [
        'TypoScript',
        'TSconfig',
        'ViewHelper',
        'TCA',
        'TYPO3_CONF_VAR',
        'YAML Form Setting',
        'YAML RTE Setting',
        'Site Language Configuration',
        'Site Configuration',
        'Console Command',
        'Console Command Argument',
        'Console Command Option',
        'File',
        'Directory',
        SiteSetSettingsDirective::FACET,
    ];

    /** The facet a value stands for: itself when it is one, "Option" otherwise. */
    public function of(string $value): string
    {
        $value = trim($value);

        return in_array($value, self::ALLOWED, true) ? $value : self::OTHER;
    }
}
