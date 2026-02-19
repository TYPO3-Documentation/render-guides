<?php

declare(strict_types=1);

namespace T3Docs\Typo3DocsTheme\Directives;

use function preg_match;
use function preg_replace;

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
    private function hasLegacyFloatClass(string $classValue): bool
    {
        return (bool) preg_match('/\bfloat-(left|right)\b/', $classValue);
    }

    private function rewriteLegacyFloatClasses(string $classValue): string
    {
        return (string) preg_replace(
            ['/\bfloat-left\b/', '/\bfloat-right\b/'],
            ['float-start', 'float-end'],
            $classValue,
        );
    }
}
