<?php

declare(strict_types=1);

namespace T3Docs\Typo3DocsTheme\TypoScriptReference;

use function is_string;
use function preg_match;
use function str_ends_with;
use function strtolower;
use function trim;

/**
 * The full path at which an option is written, such as "stdWrap.parseFunc" or
 * "options.pageTree.doktypesToShowInNewPageDragArea".
 *
 * Most options have an anchor that follows their path, "stdwrap-parsefunc".
 * Many TSconfig options do not: "options.pageTree.…" has the anchor
 * "useroptions-pagetree-…". Those declare their path in a field of its own,
 * "User TSconfig path" or "Page TSconfig path", which is found by its name
 * ending in "path".
 */
final class OptionPaths
{
    /**
     * A full path. A name alone, "wrap", is no path: the TypoScript reference
     * documents eleven options of that name.
     */
    public const PATTERN = '/^[A-Za-z][A-Za-z0-9_]*(?:\.[A-Za-z0-9_]+)+$/';

    /**
     * The paths the fields of an option declare. A path with a placeholder,
     * "mod.wizards.newContentElement.wizardItems.[group].before", names no
     * option a role can write, and is left out.
     *
     * @param array<mixed> $fields
     * @return list<string>
     */
    public static function declaredIn(array $fields): array
    {
        $paths = [];
        foreach ($fields as $name => $value) {
            if (!is_string($name) || !is_string($value) || !str_ends_with(strtolower($name), 'path')) {
                continue;
            }
            $path = trim($value);
            if (preg_match(self::PATTERN, $path) === 1) {
                $paths[] = $path;
            }
        }

        return $paths;
    }
}
