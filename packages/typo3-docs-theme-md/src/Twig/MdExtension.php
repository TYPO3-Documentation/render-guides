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

    public function inlineCell(string $content): string
    {
        $content = str_replace('|', '\\|', trim($content));

        return trim((string) preg_replace('/\s*\n\s*/', ' ', $content));
    }
}
