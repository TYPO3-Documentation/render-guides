<?php

declare(strict_types=1);

namespace T3Docs\Typo3DocsThemeMd\Twig;

use phpDocumentor\Guides\NodeRenderers\NodeRenderer;
use phpDocumentor\Guides\Nodes\Inline\InlineNodeInterface;
use phpDocumentor\Guides\Nodes\InlineCompoundNode;
use phpDocumentor\Guides\Nodes\Table\TableColumn;
use phpDocumentor\Guides\Nodes\TableNode;
use phpDocumentor\Guides\Nodes\TitleNode;
use phpDocumentor\Guides\RenderContext;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;

use function array_fill;
use function array_map;
use function count;
use function explode;
use function implode;
use function max;
use function min;
use function preg_replace;
use function rtrim;
use function str_repeat;
use function str_replace;
use function strlen;
use function substr;
use function ltrim;
use function trim;

final class MdExtension extends AbstractExtension
{
    public function __construct(
        private NodeRenderer $nodeRenderer,
    ) {}

    /** @return TwigFunction[] */
    public function getFunctions(): array
    {
        return [
            new TwigFunction('renderMdTitle', $this->renderMdTitle(...), ['is_safe' => ['md'], 'needs_context' => false]),
            new TwigFunction('renderMdTable', $this->renderMdTable(...), ['is_safe' => ['md'], 'needs_context' => true]),
            new TwigFunction('renderMdIndent', $this->renderMdIndent(...), ['is_safe' => ['md'], 'needs_context' => false]),
            new TwigFunction('renderMdPrefix', $this->renderMdPrefix(...), ['is_safe' => ['md'], 'needs_context' => false]),
        ];
    }

    /** @return TwigFilter[] */
    public function getFilters(): array
    {
        return [
            new TwigFilter('clean_content', $this->cleanContent(...)),
            new TwigFilter('plaintext', $this->plaintext(...)),
            new TwigFilter('inline_cell', $this->inlineCell(...)),
            new TwigFilter('inline_text', $this->inlineText(...)),
            new TwigFilter('md_escape', $this->escape(...)),
            new TwigFilter('md_wrap', $this->wrap(...)),
            new TwigFilter('md_yaml', $this->yamlString(...)),
        ];
    }


    public function plaintext(InlineNodeInterface $node): string
    {
        if ($node instanceof InlineCompoundNode) {
            return implode('', array_map($this->plaintext(...), $node->getChildren()));
        }

        return $node->toString();
    }

    /**
     * Trailing whitespace is meaningful in Markdown (two spaces is a hard line
     * break), so it is stripped rather than left to chance, and runs of blank
     * lines are collapsed to the single blank line that separates blocks.
     */
    public function cleanContent(string $content): string
    {
        $lines = explode("\n", $content);
        $lines = array_map(rtrim(...), $lines);
        $content = implode("\n", $lines);

        $content = preg_replace('/(\n){3,}/', "\n\n", $content) ?? $content;

        return rtrim($content) . "\n";
    }

    /** Indent every line, for nesting blocks inside list items. */
    public function renderMdIndent(string $text, int $indentNr = 1): string
    {
        $indent = str_repeat('  ', $indentNr);

        return preg_replace('/^(?!$)/m', $indent, $text) ?? $text;
    }

    /** Prefix every line, e.g. "> " for blockquotes and GFM alerts. */
    public function renderMdPrefix(string $text, string $prefix): string
    {
        $lines = explode("\n", rtrim($text));
        $lines = array_map(
            static fn(string $line): string => $line === '' ? rtrim($prefix) : $prefix . $line,
            $lines,
        );

        return implode("\n", $lines);
    }

    /** ATX headings only: they survive indentation and need no underline width. */
    public function renderMdTitle(TitleNode $node, string $content): string
    {
        return str_repeat('#', min($node->getLevel(), 6)) . ' ' . trim($content) . "\n";
    }

    /**
     * A GFM pipe table. Cell content must stay on one line, so newlines become
     * spaces and pipes are escaped.
     *
     * @param array{env: RenderContext} $context
     */
    public function renderMdTable(array $context, TableNode $node): string
    {
        $headers = $node->getHeaders();
        $data = $node->getData();

        $rows = [];
        foreach ($headers as $row) {
            $rows[] = $this->renderRow($row->getColumns(), $context['env']);
        }

        // GFM has no table without a header row; an empty one keeps the table
        // valid when the source table has only body rows.
        $columnCount = 0;
        foreach ([...$headers, ...$data] as $row) {
            $columnCount = max($columnCount, count($row->getColumns()));
        }

        if ($rows === [] && $columnCount > 0) {
            $rows[] = array_fill(0, $columnCount, '');
        }

        $out = '| ' . implode(' | ', $rows[0]) . " |\n";
        $out .= '| ' . implode(' | ', array_fill(0, count($rows[0]), '---')) . " |\n";

        foreach ($data as $row) {
            $out .= '| ' . implode(' | ', $this->renderRow($row->getColumns(), $context['env'])) . " |\n";
        }

        return $out;
    }

    /**
     * @param TableColumn[] $columns
     * @return string[]
     */
    private function renderRow(array $columns, RenderContext $env): array
    {
        $cells = [];
        foreach ($columns as $column) {
            $cells[] = $this->inlineCell(
                implode('', array_map(fn($node) => $this->nodeRenderer->render($node, $env), $column->getValue())),
            );
        }

        return $cells;
    }

    /**
     * Wrap text in an emphasis marker, keeping surrounding spaces outside it.
     *
     * reStructuredText allows emphasis whose content begins or ends with a
     * space; Markdown does not -- "* 3 *" is literal text, not emphasis. Moving
     * the spaces out preserves both the wording and the emphasis.
     */
    public function wrap(string $text, string $marker): string
    {
        $trimmed = trim($text);
        if ($trimmed === '') {
            return $text;
        }

        $lead = substr($text, 0, strlen($text) - strlen(ltrim($text)));
        $trail = substr($text, strlen(rtrim($text)));

        return $lead . $marker . $trimmed . $marker . $trail;
    }

    /**
     * Escape text that came from the source so Markdown reads it as text.
     *
     * Source prose is not Markdown, and characters that are ordinary there
     * change the document here: "__dunder__" turns bold, "array[0](x)" turns
     * into a link, a leading "#" turns into a heading, and a backslash before a
     * space disappears entirely.
     *
     * Only the characters that can actually start a construct are escaped --
     * escaping all of CommonMark's punctuation would litter the output with
     * backslashes for no gain. The line-start set is applied per line, because
     * a "#" mid-sentence is harmless.
     *
     * Never use this inside a code span: backticks already take the content
     * literally, and a backslash there would be part of the code.
     */
    public function escape(string $text): string
    {
        // The backslash first, or the escapes added below would be escaped again.
        $text = str_replace('\\', '\\\\', $text);

        // Always ambiguous: a backtick opens a code span, an asterisk opens
        // emphasis even inside a word, and brackets open a link.
        $text = (string) preg_replace('/([`*\[\]])/', '\\\\$1', $text);

        // An underscore inside a word is not emphasis in CommonMark, so
        // "snake_case" needs nothing. Only the ones at a word boundary can open
        // or close, which is what makes "__dunder__" bold.
        $text = (string) preg_replace('/(?<![A-Za-z0-9])_|_(?![A-Za-z0-9])/', '\\\\$0', $text);

        // "<" only matters when something could read as a tag or an autolink.
        $text = (string) preg_replace('/<(?=[A-Za-z\/!?])/', '\\\\<', $text);

        // Block markers only count at the start of a line, and only when a
        // space follows -- "-1" is not a list and "#tag" is not a heading.
        $text = (string) preg_replace('/^(\s*)([#>])(?=\s|$)/m', '$1\\\\$2', $text);
        $text = (string) preg_replace('/^(\s*)([-+])(?=\s)/m', '$1\\\\$2', $text);

        // Only punctuation can be backslash-escaped, so an ordered list marker
        // is defused at its dot rather than at its digits: "\1." would leave
        // the backslash visible to the reader.
        return (string) preg_replace('/^(\s*)(\d+)([.)])(?=\s)/m', '$1$2\\\\$3', $text);
    }

    /**
     * A scalar as a double-quoted YAML string, for the front matter.
     *
     * Always quoted rather than only when needed: a title that begins with a
     * digit, holds a colon or reads as "yes" would otherwise come back out of
     * a parser as a number, a mapping or a boolean.
     */
    public function yamlString(string $value): string
    {
        return '"' . str_replace(['\\', '"', "\n"], ['\\\\', '\\"', ' '], $value) . '"';
    }

    /** Collapse a rendered block onto one line, for places that cannot hold one. */
    public function inlineText(string $content): string
    {
        return trim((string) preg_replace('/\s*\n\s*/', ' ', trim($content)));
    }

    /** As inlineText, plus the pipe escaping a GFM table cell needs. */
    public function inlineCell(string $content): string
    {
        return $this->inlineText(str_replace('|', '\\|', $content));
    }
}
