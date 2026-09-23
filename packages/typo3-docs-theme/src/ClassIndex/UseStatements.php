<?php

declare(strict_types=1);

namespace T3Docs\Typo3DocsTheme\ClassIndex;

use phpDocumentor\Guides\Nodes\CodeNode;

use function preg_match_all;
use function preg_split;
use function rtrim;
use function str_contains;
use function trim;

use const PREG_SPLIT_NO_EMPTY;

/**
 * The classes a PHP example imports.
 *
 * A "use" statement is the one place where an example names a class in full,
 * and it names exactly the classes the example is about. What the body of the
 * example then does with them -- "new", a type hint, a static call -- repeats
 * the short name and is left alone.
 *
 * A trait used inside a class body is written without a namespace and is not
 * an import; "use function" and "use const" name no class either.
 */
final class UseStatements
{
    private const PATTERN = '/^[ \t]*use[ \t]+(?!function\b|const\b)([A-Za-z_\\\\][A-Za-z0-9_\\\\]*)(?:[ \t]*\{([^}]*)\}|[ \t]+as[ \t]+[A-Za-z_][A-Za-z0-9_]*)?[ \t]*;/mi';

    /** @return list<string> the fully qualified names, in the order the example imports them */
    public function of(CodeNode $node): array
    {
        if ($node->getLanguage() !== 'php') {
            return [];
        }

        if (preg_match_all(self::PATTERN, $node->toString(), $matches, PREG_SET_ORDER) === 0) {
            return [];
        }

        $names = [];
        foreach ($matches as $match) {
            $prefix = $match[1];
            $group = trim($match[2] ?? '');
            if ($group === '') {
                $names[] = $prefix;
                continue;
            }

            // "use Foo\{Bar, Baz as Qux};" imports each name below the prefix.
            foreach (preg_split('/,/', $group, -1, PREG_SPLIT_NO_EMPTY) ?: [] as $member) {
                $member = trim(preg_replace('/\s+as\s+[A-Za-z_][A-Za-z0-9_]*$/i', '', trim($member)) ?? '');
                if ($member === '') {
                    continue;
                }

                $names[] = rtrim($prefix, '\\') . '\\' . $member;
            }
        }

        $classes = [];
        foreach ($names as $name) {
            // Without a namespace it is a trait of the example itself, or a
            // class of the global namespace, and says nothing worth indexing.
            if (!str_contains(trim($name, '\\'), '\\')) {
                continue;
            }

            $classes[] = $name;
        }

        return $classes;
    }
}
