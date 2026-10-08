<?php

declare(strict_types=1);

namespace T3Docs\Typo3DocsTheme\TypoScriptReference;

use function count;
use function preg_match;
use function preg_quote;

/**
 * Where the TypoScript reference documents an object type, such as
 * "PAGEVIEW", "COA_INT" or "PAGE", found by its anchor or its page title.
 *
 * Object types are no options, so "confvals.json" does not list them. Each
 * has a page or a section of its own instead, which the inventory lists, but
 * neither by one rule alone:
 *
 * - A content object has the anchor "cobj-" and its name, "cobj-coa-int";
 *   "PAGE" has "page". The headline names the type, though not always alone:
 *   "Content object array - COA, COA_INT", "PAGE object type in TypoScript".
 *   An anchor counts only where its headline names the type, so an anchor
 *   that happens to match means nothing.
 * - Some types have no such anchor, only a page titled by the type alone:
 *   the GIFBUILDER objects "EMBOSS" or "OUTLINE". "TEXT" and "IMAGE" are both
 *   a content object and a GIFBUILDER object; the anchor of the content
 *   object is found first, which is the one a ":typoscript:" role means.
 *
 * A type that is no longer documented, "FILE" or "IMAGEBUTTON", is found by
 * neither.
 *
 * @phpstan-type ObjectType array{title: string, url: string}
 */
final class ObjectTypes
{
    /**
     * @param string $slug the type as an anchor writes it: "coa-int"
     * @param callable(string): (ObjectType|null) $label the headline with this anchor
     * @param array<string, list<string>> $pagesByTitle the address of every page, by its title
     * @return ObjectType|null
     */
    public static function find(string $type, string $slug, callable $label, array $pagesByTitle): ?array
    {
        foreach (['cobj-' . $slug, $slug] as $anchor) {
            $headline = $label($anchor);
            if ($headline !== null && self::names($headline['title'], $type)) {
                return $headline;
            }
        }

        $pages = $pagesByTitle[$type] ?? [];
        if (count($pages) === 1) {
            return ['title' => $type, 'url' => $pages[0]];
        }

        return null;
    }

    /** Whether the title names the type as a word of its own: "COA" in "COA, COA_INT", not in "COA_INT" alone. */
    private static function names(string $title, string $type): bool
    {
        return preg_match('/(?<![A-Z0-9_])' . preg_quote($type, '/') . '(?![A-Z0-9_])/', $title) === 1;
    }
}
