<?php

declare(strict_types=1);

namespace T3Docs\Typo3DocsTheme\CodeFolding;

use function array_pop;
use function array_reverse;
use function count;
use function explode;
use function implode;
use function preg_match;
use function preg_split;
use function sprintf;
use function str_ends_with;
use function str_repeat;
use function str_starts_with;
use function strlen;
use function strpos;
use function strrpos;
use function substr;
use function substr_count;

use const PREG_SPLIT_DELIM_CAPTURE;
use const PREG_SPLIT_NO_EMPTY;

/**
 * Wraps ranges of lines of a highlighted code block so they can be folded.
 *
 * The highlighter's markup does not respect line boundaries: a comment or a
 * string spanning three lines is one span across both newlines. Each line is
 * therefore closed at its end and reopened on the next, so that a folded range
 * is a well-formed element of its own.
 *
 * Every character of the code stays in the markup, folded or not, which is
 * what lets the copy button take the whole block from the element's text.
 */
final class HtmlLineFolder
{
    /**
     * @param string $html the highlighter's output, "<pre ...><code ...>...</code></pre>"
     * @param list<array{int, int}> $ranges first and last line of each folded range
     */
    public function fold(string $html, array $ranges): string
    {
        if ($ranges === []) {
            return $html;
        }

        $open = strpos($html, '<code');
        $start = $open === false ? false : strpos($html, '>', $open);
        $end = strrpos($html, '</code>');
        if ($start === false || $end === false || $end < $start) {
            return $html;
        }

        $inner = substr($html, $start + 1, $end - $start - 1);
        $lines = $this->balancedLines($inner);

        $folded = [];
        $lineNumber = 1;
        $count = count($lines);
        foreach ($ranges as [$from, $to]) {
            if ($from > $count) {
                break;
            }

            $to = $to > $count ? $count : $to;
            while ($lineNumber < $from) {
                $folded[] = $lines[$lineNumber - 1] . "\n";
                $lineNumber++;
            }

            $body = [];
            while ($lineNumber <= $to) {
                // The newline that ends the last folded line is folded with
                // it, or a hidden range would leave an empty line behind.
                $body[] = $lines[$lineNumber - 1] . ($lineNumber < $count ? "\n" : '');
                $lineNumber++;
            }

            $hidden = $to - $from + 1;
            // The button has no text of its own -- its label comes from CSS --
            // so the block's text, which the copy button takes, stays the code.
            $label = sprintf('%d %s', $hidden, $hidden === 1 ? 'line' : 'lines');
            $folded[] = sprintf(
                '<button type="button" class="code-fold-toggle" data-fold-label="%1$s"'
                . ' aria-label="Show %1$s"></button>'
                . '<span class="code-fold">%2$s</span>',
                $label,
                implode('', $body),
            );
        }

        while ($lineNumber <= $count) {
            $folded[] = $lines[$lineNumber - 1] . ($lineNumber < $count ? "\n" : '');
            $lineNumber++;
        }

        return substr($html, 0, $start + 1) . implode('', $folded) . substr($html, $end);
    }

    /**
     * The lines of the code, each a well-formed piece of markup.
     *
     * Line numbers and emphasized lines are wrapped around each line after
     * highlighting, by splitting at the newlines without regard to the
     * highlighter's spans -- so a comment spanning lines leaves the wrappers
     * nested in one another. They are taken off before the split and put back
     * around each line after it.
     *
     * @return list<string>
     */
    private function balancedLines(string $html): array
    {
        $wrappers = [];
        $content = [];
        foreach (explode("\n", $html) as $index => $line) {
            $wrappers[$index] = ['', 0];
            if (preg_match('#^(?:<span data-(?:line-number="\d+"|emphasize-line)>)+#', $line, $matches) === 1) {
                $count = substr_count($matches[0], '<span');
                $line = substr($line, strlen($matches[0]));
                $closed = 0;
                for (; $closed < $count && str_ends_with($line, '</span>'); $closed++) {
                    $line = substr($line, 0, -strlen('</span>'));
                }

                $wrappers[$index] = [$matches[0], $closed];
            }

            $content[] = $line;
        }

        $lines = $this->splitLines(implode("\n", $content));
        foreach ($lines as $index => $line) {
            [$open, $closed] = $wrappers[$index] ?? ['', 0];
            $lines[$index] = $open . $line . str_repeat('</span>', $closed);
        }

        return $lines;
    }

    /**
     * The lines of highlighted markup, each closing what it opened and
     * reopening what an earlier line left open.
     *
     * @return list<string>
     */
    private function splitLines(string $html): array
    {
        $tokens = preg_split('/(<[^>]*>)/', $html, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY);
        if ($tokens === false) {
            return explode("\n", $html);
        }

        $lines = [];
        $current = '';
        /** @var list<string> $stack the opening tags still open, outermost first */
        $stack = [];

        foreach ($tokens as $token) {
            if (str_starts_with($token, '</')) {
                array_pop($stack);
                $current .= $token;
                continue;
            }

            if (str_starts_with($token, '<')) {
                $current .= $token;
                if (preg_match('#/\s*>$#', $token) !== 1 && preg_match('#^<(br|wbr|img|input)\b#i', $token) !== 1) {
                    $stack[] = $token;
                }

                continue;
            }

            $pieces = explode("\n", $token);
            $last = count($pieces) - 1;
            foreach ($pieces as $index => $piece) {
                $current .= $piece;
                if ($index === $last) {
                    break;
                }

                $lines[] = $current . $this->closing($stack);
                $current = implode('', $stack);
            }
        }

        $lines[] = $current;

        return $lines;
    }

    /** @param list<string> $stack */
    private function closing(array $stack): string
    {
        $closing = '';
        foreach (array_reverse($stack) as $tag) {
            preg_match('#^<\s*([a-zA-Z0-9-]+)#', $tag, $matches);
            $closing .= '</' . ($matches[1] ?? 'span') . '>';
        }

        return $closing;
    }
}
