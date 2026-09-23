<?php

declare(strict_types=1);

namespace T3Docs\Typo3DocsTheme\CodeFolding;

use phpDocumentor\Guides\Nodes\CodeNode;

use function array_values;
use function count;
use function explode;
use function preg_match;
use function str_contains;
use function str_ends_with;
use function str_starts_with;
use function strtolower;
use function trim;

/**
 * Which lines of a code block are folded away until the reader expands them.
 *
 * An author names the lines that matter with ":visible-lines:"; everything
 * else is folded. Without the option, a PHP example folds its header -- the
 * open tag, declare, namespace and use statements -- which is complete for the
 * linter but rarely what the example is about. ":visible-lines: all" switches
 * folding off.
 *
 * Emphasized lines are never folded: the author marked them to be seen.
 * Line numbers count from 1, the way ":emphasize-lines:" does.
 */
final class FoldedLines
{
    public const OPTION = 'visible-lines';

    /** Folding fewer lines than this saves nothing and costs a click. */
    private const MIN_HEADER_LINES = 3;

    /**
     * The folded ranges of a code block, in order and without overlap.
     *
     * @return list<array{int, int}> first and last line of each range
     */
    public function of(CodeNode $node): array
    {
        $lines = explode("\n", $node->getValue());

        // A file's final newline is not a line of its own.
        $lineCount = count($lines);
        if ($lineCount > 1 && $lines[$lineCount - 1] === '') {
            $lineCount--;
        }

        $option = trim((string) $node->getOption(self::OPTION, ''));
        if (strtolower($option) === 'all') {
            return [];
        }

        $emphasized = self::parse((string) $node->getEmphasizeLines()) ?? [];

        // An invalid value was reported by the directive and falls through to
        // the default, like no value at all.
        $visible = $option === '' ? null : self::parse($option);
        if ($visible !== null) {
            return self::complement([...$visible, ...$emphasized], $lineCount);
        }

        if (strtolower((string) $node->getLanguage()) !== 'php') {
            return [];
        }

        $header = $this->phpHeaderLength($lines);
        foreach ($emphasized as $line) {
            $header = $line <= $header ? $line - 1 : $header;
        }

        return $header < self::MIN_HEADER_LINES ? [] : [[1, $header]];
    }

    /**
     * The lines an option value names, or null for a value that is not a list
     * of lines -- "all" included.
     *
     * @return list<int>|null
     */
    public static function parse(string $value): array|null
    {
        $value = trim($value);
        if ($value === '' || strtolower($value) === 'all') {
            return null;
        }

        $lines = [];
        foreach (explode(',', $value) as $part) {
            if (preg_match('/^\s*(\d+)\s*(?:-\s*(\d+)\s*)?$/', $part, $matches) !== 1) {
                return null;
            }

            $from = (int) $matches[1];
            $to = isset($matches[2]) ? (int) $matches[2] : $from;
            if ($from < 1 || $to < $from) {
                return null;
            }

            for ($line = $from; $line <= $to; $line++) {
                $lines[$line] = $line;
            }
        }

        return array_values($lines);
    }

    /** Whether an option value is one the directive can accept. */
    public static function isValid(string $value): bool
    {
        return strtolower(trim($value)) === 'all' || self::parse($value) !== null;
    }

    /**
     * @param list<int> $visible
     * @return list<array{int, int}>
     */
    private static function complement(array $visible, int $lineCount): array
    {
        $shown = [];
        foreach ($visible as $line) {
            $shown[$line] = true;
        }

        $ranges = [];
        $start = null;
        for ($line = 1; $line <= $lineCount; $line++) {
            if (!isset($shown[$line])) {
                $start ??= $line;
                continue;
            }

            if ($start !== null) {
                $ranges[] = [$start, $line - 1];
                $start = null;
            }
        }

        if ($start !== null) {
            $ranges[] = [$start, $lineCount];
        }

        // Folding every line leaves nothing to look at.
        if ($ranges === [[1, $lineCount]]) {
            return [];
        }

        return $ranges;
    }

    /**
     * How many lines the header of a PHP file takes, or 0 when it is too short
     * to be worth folding.
     *
     * The header runs to its last open tag, declare, namespace or use
     * statement, and takes the blank lines after it. Comments before that
     * point go with it; a docblock after it belongs to what follows and stays.
     *
     * @param list<string> $lines
     */
    private function phpHeaderLength(array $lines): int
    {
        $last = -1;
        $inComment = false;
        $inGroupUse = false;

        foreach ($lines as $index => $line) {
            $code = trim($line);

            if ($inComment) {
                $inComment = !str_contains($code, '*/');
                continue;
            }

            if ($inGroupUse) {
                $inGroupUse = !str_ends_with($code, ';');
                $last = $index;
                continue;
            }

            if ($code === '' || str_starts_with($code, '//')) {
                continue;
            }

            if (str_starts_with($code, '#') && !str_starts_with($code, '#[')) {
                continue;
            }

            if (str_starts_with($code, '/*')) {
                $inComment = !str_contains($code, '*/');
                continue;
            }

            if (
                str_starts_with($code, '<?php')
                || str_starts_with($code, 'declare(')
                || (str_starts_with($code, 'namespace ') && str_ends_with($code, ';'))
            ) {
                $last = $index;
                continue;
            }

            if (str_starts_with($code, 'use ')) {
                $inGroupUse = !str_ends_with($code, ';');
                $last = $index;
                continue;
            }

            break;
        }

        if ($last < 0) {
            return 0;
        }

        $end = $last;
        $lastLine = count($lines) - 1;
        while ($end < $lastLine && trim($lines[$end + 1]) === '') {
            $end++;
        }

        $length = $end + 1;
        $hasRest = false;
        for ($index = $length; $index <= $lastLine; $index++) {
            if (trim($lines[$index]) !== '') {
                $hasRest = true;
                break;
            }
        }

        return $length >= self::MIN_HEADER_LINES && $hasRest ? $length : 0;
    }
}
