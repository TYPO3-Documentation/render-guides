<?php

declare(strict_types=1);

namespace T3Docs\Typo3DocsTheme\Twig;

use phpDocumentor\Guides\Nodes\CodeNode;
use T3Docs\Typo3DocsTheme\CodeFolding\FoldedLines;
use T3Docs\Typo3DocsTheme\CodeFolding\HtmlLineFolder;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;

/**
 * "foldedLines(node)" says which lines of a code block fold away, and
 * "|fold_lines(ranges)" wraps them in the highlighted markup. @see FoldedLines
 */
final class CodeFoldingExtension extends AbstractExtension
{
    public function __construct(
        private readonly FoldedLines $foldedLines,
        private readonly HtmlLineFolder $htmlLineFolder,
    ) {}

    /** @return list<TwigFunction> */
    public function getFunctions(): array
    {
        return [
            new TwigFunction('foldedLines', fn(CodeNode $node): array => $this->foldedLines->of($node)),
        ];
    }

    /** @return list<TwigFilter> */
    public function getFilters(): array
    {
        return [
            new TwigFilter('fold_lines', $this->foldLines(...), ['is_safe' => ['html']]),
        ];
    }

    /** @param list<array{int, int}> $ranges as foldedLines() returns them */
    public function foldLines(string $html, array $ranges): string
    {
        return $this->htmlLineFolder->fold($html, $ranges);
    }
}
