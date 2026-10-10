<?php

declare(strict_types=1);

namespace T3Docs\Typo3DocsTheme\TypoScriptReference;

use function preg_match;

/**
 * The confval a ":typoscript:" or ":tsconfig:" role names in angle brackets,
 * where the code alone does not tell it: "module.tx_extbase <some-key>".
 *
 * The name is written as ":confval:" writes it, the confval's own name or that
 * of another manual with its interlink key: "<t3coreapi:some-key>". The code
 * before the brackets is what the page shows.
 */
final class NamedConfval
{
    /** The key under which the code carries the confval it names. */
    public const INFO = 'namedConfval';

    /** The key under which the code carries the role that names it. */
    public const ROLE_INFO = 'namedConfvalRole';

    /**
     * A path or a name, then the name of a confval in angle brackets. A key
     * may start with an underscore: "_LOCAL_LANG", "_CSS_DEFAULT_STYLE". Other
     * code that ends in angle brackets names nothing: HTML in a wrap,
     * "<div style="…"> | </div>", or an include, "<INCLUDE_TYPOSCRIPT: …>".
     */
    private const PATTERN = '/^([A-Za-z_][A-Za-z0-9_.\[\]]*)\s+<([A-Za-z0-9][A-Za-z0-9_.\/:-]*)>$/';

    /**
     * The code to show and the confval it names, or the code as written and "".
     *
     * @return array{string, string}
     */
    public static function split(string $code): array
    {
        if (preg_match(self::PATTERN, $code, $matches) !== 1) {
            return [$code, ''];
        }

        return [$matches[1], $matches[2]];
    }
}
