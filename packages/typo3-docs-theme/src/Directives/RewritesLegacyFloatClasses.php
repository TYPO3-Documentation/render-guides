<?php

declare(strict_types=1);

namespace T3Docs\Typo3DocsTheme\Directives;

use function preg_match;
use function preg_replace_callback;

/**
 * Shared logic for detecting and rewriting deprecated Bootstrap 4 float class names.
 *
 * Rewrites `float-left` → `float-start` and `float-right` → `float-end` (Bootstrap 5
 * logical properties). Used by both FigureDirective and ImageDirective.
 *
 * @see FigureDirective
 * @see ImageDirective
 */
trait RewritesLegacyFloatClasses
{
    /**
     * Anchored on class boundaries rather than \b, which also breaks at a
     * hyphen: a project class such as "my-float-left" is not Bootstrap's.
     */
    private const LEGACY_FLOAT_CLASS_PATTERN = '/(?<![\w-])float-(left|right)(?![\w-])/';

    private function hasLegacyFloatClass(string $classValue): bool
    {
        return (bool) preg_match(self::LEGACY_FLOAT_CLASS_PATTERN, $classValue);
    }

    private function rewriteLegacyFloatClasses(string $classValue): string
    {
        return (string) preg_replace_callback(
            self::LEGACY_FLOAT_CLASS_PATTERN,
            static fn(array $matches): string => $matches[1] === 'left' ? 'float-start' : 'float-end',
            $classValue,
        );
    }
}
