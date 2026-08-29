<?php

declare(strict_types=1);

/**
 * This file is part of phpDocumentor.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @link https://phpdoc.org
 */

namespace T3Docs\Typo3DocsTheme\Lint;

use function array_search;
use function count;
use function explode;
use function mb_strlen;
use function sprintf;
use function str_contains;
use function str_repeat;
use function strlen;
use function substr;
use function trim;

/**
 * Source lint rule (#1157): detects skipped heading levels.
 *
 * reStructuredText assigns heading levels by the order in which adornment
 * styles are first encountered, and the parser normalises the result — so a
 * skip (e.g. a level-1 heading followed directly by a level-3 style) is only
 * visible in the raw source, not in the parsed AST. This rule walks the
 * authored adornment sequence, assigns each distinct style a level by
 * first-encounter order, and warns when a heading is more than one level deeper
 * than the preceding heading.
 *
 * Heading detection is intentionally limited to column 0: reStructuredText
 * section titles and their adornments are never indented, so indented lines
 * (e.g. the contents of `::` literal or `.. code-block::` blocks, which often
 * contain `===`/`---` separators) are ignored and cannot be misread as
 * headings.
 *
 * The set of adornment characters follows the reStructuredText specification;
 * `.` is excluded so it does not clash with the `..` directive/comment marker.
 */
final class SkippedHeadingLevelSourceRule implements SourceLintRule
{
    private const ADORNMENT_CHARACTERS = '=-`:\'"~^_*+#<>';

    public function lint(string $contents): array
    {
        $lines = explode("\n", $contents);
        $lineCount = count($lines);

        /** @var list<string> $styleOrder distinct adornment styles in first-encounter order */
        $styleOrder = [];
        $previousLevel = 0;
        $warnings = [];

        for ($i = 0; $i < $lineCount; $i++) {
            [$title, $style, $consumed] = $this->matchHeading($lines, $i);
            if ($title === null) {
                continue;
            }

            $existing = array_search($style, $styleOrder, true);
            if ($existing === false) {
                $styleOrder[] = $style;
                $level = count($styleOrder);
            } else {
                $level = $existing + 1;
            }

            if ($level > $previousLevel + 1) {
                $warnings[] = sprintf('Heading "%s" (line %d) skips a heading level.', trim($title), $i + 1);
            }

            $previousLevel = $level;
            $i += $consumed - 1;
        }

        return $warnings;
    }

    /**
     * Try to match a heading starting at $lines[$index], supporting both the
     * underline-only and overline+underline forms.
     *
     * @param list<string> $lines
     * @return array{0: string|null, 1: string, 2: int} [title, style, lines consumed]
     */
    private function matchHeading(array $lines, int $index): array
    {
        $line = $lines[$index];

        // Overline + title + underline: adornment, title, matching adornment.
        $overChar = $this->adornmentChar($line);
        if ($overChar !== null && isset($lines[$index + 2]) && $this->isTitleLine($lines[$index + 1])) {
            $underChar = $this->adornmentChar($lines[$index + 2]);
            $title = $lines[$index + 1];
            if ($underChar === $overChar && $this->coversTitle($line, $title) && $this->coversTitle($lines[$index + 2], $title)) {
                return [$title, 'over:' . $overChar, 3];
            }
        }

        // Title + underline.
        if ($this->isTitleLine($line) && isset($lines[$index + 1])) {
            $underChar = $this->adornmentChar($lines[$index + 1]);
            if ($underChar !== null && $this->coversTitle($lines[$index + 1], $line)) {
                return [$line, 'under:' . $underChar, 2];
            }
        }

        return [null, '', 1];
    }

    /**
     * Return the single adornment character a line is built from, or null if the
     * line is not an adornment line.
     */
    private function adornmentChar(string $line): string|null
    {
        if ($this->isIndented($line)) {
            return null;
        }
        $trimmed = trim($line);
        if ($trimmed === '' || strlen($trimmed) < 2) {
            return null;
        }
        $char = substr($trimmed, 0, 1);
        if (!str_contains(self::ADORNMENT_CHARACTERS, $char)) {
            return null;
        }

        return $trimmed === str_repeat($char, strlen($trimmed)) ? $char : null;
    }

    private function isTitleLine(string $line): bool
    {
        return !$this->isIndented($line) && trim($line) !== '' && $this->adornmentChar($line) === null;
    }

    private function isIndented(string $line): bool
    {
        return $line !== '' && ($line[0] === ' ' || $line[0] === "\t");
    }

    private function coversTitle(string $adornment, string $title): bool
    {
        return mb_strlen(trim($adornment)) >= mb_strlen(trim($title));
    }
}
