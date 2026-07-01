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

namespace T3Docs\Typo3DocsTheme\Compiler\NodeTransformers;

use phpDocumentor\Guides\Compiler\CompilerContextInterface;
use phpDocumentor\Guides\Nodes\SectionNode;

use function array_filter;
use function array_map;
use function array_unique;
use function array_values;
use function explode;
use function preg_match;
use function preg_quote;
use function sprintf;
use function trim;

/**
 * Opt-in heading lint rule (#1157): warns when a section heading contains a
 * discouraged phrase (e.g. "Non-Composer mode").
 *
 * The phrase list defaults to {@see self::DEFAULT_DISCOURAGED_PHRASES} and can
 * be overridden via the comma-separated `lint_discouraged_phrases` setting.
 * Matching is case-insensitive and bound to whole words, so a short phrase does
 * not match inside a larger word (e.g. "id" does not flag "Identifier").
 */
final class LintDiscouragedPhrasesTransformer extends AbstractHeadingLintTransformer
{
    /** @var list<string> */
    private const DEFAULT_DISCOURAGED_PHRASES = ['Non-Composer mode'];

    protected function checkSection(SectionNode $section, CompilerContextInterface $compilerContext): void
    {
        $heading = $section->getTitle()->toString();

        foreach ($this->getDiscouragedPhrases() as $phrase) {
            if (preg_match('/\b' . preg_quote($phrase, '/') . '\b/iu', $heading) !== 1) {
                continue;
            }
            $this->logger->warning(
                sprintf('Heading "%s" contains the discouraged phrase "%s".', $heading, $phrase),
                $compilerContext->getLoggerInformation(),
            );
        }
    }

    /** @return list<string> */
    private function getDiscouragedPhrases(): array
    {
        $configured = trim($this->themeSettings->getSettings('lint_discouraged_phrases', ''));
        if ($configured === '') {
            return self::DEFAULT_DISCOURAGED_PHRASES;
        }

        $phrases = array_filter(array_map(trim(...), explode(',', $configured)), static fn(string $phrase): bool => $phrase !== '');

        return array_values(array_unique($phrases));
    }
}
