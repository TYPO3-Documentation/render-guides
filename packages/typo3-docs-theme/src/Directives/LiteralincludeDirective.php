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

namespace T3Docs\Typo3DocsTheme\Directives;

use phpDocumentor\Guides\Nodes\CodeNode;
use phpDocumentor\Guides\Nodes\Node;
use phpDocumentor\Guides\ParserContext;
use phpDocumentor\Guides\RestructuredText\Directives\BaseDirective;
use phpDocumentor\Guides\RestructuredText\Directives\OptionMapper\CodeNodeOptionMapper;
use phpDocumentor\Guides\RestructuredText\Parser\BlockContext;
use phpDocumentor\Guides\RestructuredText\Parser\Directive;
use Psr\Log\LoggerInterface;
use RuntimeException;
use SebastianBergmann\Diff\Differ;
use SebastianBergmann\Diff\Output\UnifiedDiffOutputBuilder;
use T3Docs\Typo3DocsTheme\CodeFolding\FoldedLines;

use function count;
use function explode;
use function implode;
use function rtrim;
use function sprintf;
use function str_contains;
use function str_starts_with;

final class LiteralincludeDirective extends BaseDirective
{
    public function __construct(
        private readonly CodeNodeOptionMapper $codeNodeOptionMapper,
        private readonly LoggerInterface      $logger,
        private readonly VisibleLinesOption   $visibleLines,
    ) {}

    public function getName(): string
    {
        return 'literalinclude';
    }

    private function detectLanguageFromExtension(string $path): ?string
    {
        $extensionMap = [
            'html' => 'html',
            'php' => 'php',
            'typoscript' => 'typoscript',
            'tsconfig' => 'typoscript',
            'xml' => 'xml',
            'json' => 'json',
            'yaml' => 'yaml',
            'yml' => 'yaml',
            'js' => 'javascript',
            'css' => 'css',
            'scss' => 'scss',
            'ts' => 'typescript',
            'txt' => 'plaintext',
            'htaccess' => 'plaintext',
            'rst' => 'rest',
            'diff' => 'diff',
        ];

        $extension = pathinfo($path, PATHINFO_EXTENSION);
        if (isset($extensionMap[$extension])) {
            return $extensionMap[$extension];
        }
        return null;
    }

    private function read(ParserContext $parserContext, string $file): string
    {
        $path = $parserContext->absoluteRelativePath($file);
        $origin = $parserContext->getOrigin();
        if (!$origin->has($path)) {
            throw new RuntimeException(
                sprintf('Include "%s" (%s) does not exist or is not readable.', $file, $path),
            );
        }

        $contents = $origin->read($path);
        if ($contents === false) {
            throw new RuntimeException(sprintf('Could not load file from path %s', $path));
        }

        return $contents;
    }

    /**
     * The changes from the file ":diff:" names to the included one, as a
     * unified diff, the way Sphinx shows them: an author keeps the file as it
     * was and as it is, instead of a diff written by hand that nothing checks.
     *
     * Null when the two files are the same, which leaves nothing to show but
     * the diff's two header lines.
     */
    private function diff(ParserContext $parserContext, Directive $directive, string $contents, BlockContext $blockContext): ?string
    {
        $original = $directive->getOptionString('diff');
        $differ = new Differ(new UnifiedDiffOutputBuilder(
            sprintf("--- %s\n+++ %s\n", $original, $directive->getData()),
            true,
        ));
        $diff = $differ->diff($this->read($parserContext, $original), $contents);

        if (!str_contains($diff, "\n@@ ")) {
            $this->logger->warning(
                sprintf(
                    '`..  literalinclude:: %s` is the same as its `:diff: %s`, so there is no difference to show. Showing the file instead.',
                    $directive->getData(),
                    $original,
                ),
                $blockContext->getLoggerInformation(),
            );
            return null;
        }

        return rtrim($diff, "\n");
    }

    /** {@inheritDoc} */
    public function processNode(
        BlockContext $blockContext,
        Directive    $directive,
    ): Node {
        $parser = $blockContext->getDocumentParserContext()->getParser();
        $parserContext = $parser->getParserContext();
        $path = $parserContext->absoluteRelativePath($directive->getData());
        $contents = $this->read($parserContext, $directive->getData());

        $language = $this->detectLanguageFromExtension($path);
        $diff = $directive->hasOption('diff') ? $this->diff($parserContext, $directive, $contents, $blockContext) : null;
        if ($diff !== null) {
            $contents = $diff;
            // What is shown is a diff, whatever the two files are written in
            $language = 'diff';
        } elseif ($directive->hasOption('language')) {
            $language = $directive->getOptionString('language');
        }
        if ($language === null) {
            $this->logger->warning(
                sprintf(
                    'Language of `..  literalinclude:: %s` could not be autodetected. '
                    . 'Use property `:language: [langugage]` to explicitly set the language. '
                    . 'Defaulting to `plaintext`. ',
                    $directive->getData()
                ),
                $blockContext->getLoggerInformation()
            );
            $language = 'plaintext';
        }

        $codeNode = new CodeNode(explode("\n", $contents), $language);
        $this->codeNodeOptionMapper->apply($codeNode, $directive->getOptions(), $blockContext);

        $path = '/' . trim($path, '/');
        $codeNode = $codeNode->withKeepExistingOptions([
            'source' => $directive->getData(),
            'path' => $path,
        ]);

        if (!$codeNode instanceof CodeNode) {
            return $codeNode;
        }

        if ($diff !== null && !$directive->hasOption(FoldedLines::OPTION)) {
            $codeNode = $codeNode->withOptions([FoldedLines::OPTION => $this->changedLines($diff)]);
        }

        return $codeNode instanceof CodeNode
            ? $this->visibleLines->apply($codeNode, $directive, $blockContext)
            : $codeNode;
    }

    /**
     * The lines of a diff that add or remove something, and the unchanged line
     * right above and below each change, as ":visible-lines:" takes them: what
     * a diff is about, and where in the file it is. The file header, the hunk
     * headers and the rest of the unchanged lines are folded, one click away.
     */
    private function changedLines(string $diff): string
    {
        $lines = explode("\n", $diff);
        $visible = [];
        foreach ($lines as $index => $line) {
            if (!$this->isChange($line)) {
                continue;
            }
            $visible[$index] = true;
            foreach ([$index - 1, $index + 1] as $neighbour) {
                if (str_starts_with($lines[$neighbour] ?? '', ' ')) {
                    $visible[$neighbour] = true;
                }
            }
        }

        $ranges = [];
        $start = null;
        for ($index = 0; $index <= count($lines); $index++) {
            if (isset($visible[$index])) {
                $start ??= $index + 1;
                continue;
            }
            if ($start !== null) {
                $ranges[] = $start === $index ? (string) $start : $start . '-' . $index;
                $start = null;
            }
        }

        return implode(', ', $ranges);
    }

    private function isChange(string $line): bool
    {
        return ($line !== '' && ($line[0] === '+' || $line[0] === '-'))
            && !str_starts_with($line, '+++ ') && !str_starts_with($line, '--- ');
    }
}
