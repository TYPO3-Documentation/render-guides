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
use function array_merge;
use function array_slice;
use function count;
use function explode;
use function in_array;
use function preg_match;
use function preg_split;
use function sprintf;
use function strtolower;
use function trim;

/**
 * Opt-in heading lint rule (#1157): warns when a heading looks like Title Case
 * rather than sentence case (TYPO3 documentation uses sentence case).
 *
 * This is a deliberately conservative heuristic to avoid false positives on the
 * many legitimately-capitalized terms in technical headings:
 *  - the first word is ignored (sentence case capitalizes it);
 *  - only simple Capitalized words (^[A-Z][a-z]+$) are counted, so ALL-CAPS
 *    acronyms (TYPO3, API, PHP) and CamelCase identifiers (ViewHelper) are
 *    never flagged;
 *  - known proper nouns are exempt via {@see self::DEFAULT_ALLOWED_WORDS} plus
 *    the comma-separated `lint_heading_allowed_words` setting;
 *  - a heading is only flagged when at least two such words occur, since a
 *    single capitalized word is most likely a proper noun.
 */
final class SentenceCaseHeadingLintTransformer extends AbstractHeadingLintTransformer
{
    /** @var list<string> */
    private const DEFAULT_ALLOWED_WORDS = [
        'Composer', 'Fluid', 'Extbase', 'Camino', 'Bootstrap', 'Symfony', 'Twig',
        'Docker', 'Packagist', 'Git', 'Vite', 'Node', 'Sass', 'Markdown', 'Linux',
        'Windows', 'English', 'German',
    ];

    private const TITLE_CASE_THRESHOLD = 2;

    protected function checkSection(SectionNode $section, CompilerContextInterface $compilerContext): void
    {
        $heading = $section->getTitle()->toString();
        $words = preg_split('/\s+/u', trim($heading), -1, PREG_SPLIT_NO_EMPTY);
        if ($words === false || count($words) < 2) {
            return;
        }

        $allowed = $this->getAllowedWords();
        $titleCaseWords = 0;
        // Skip the first word: sentence case capitalizes it legitimately.
        foreach (array_slice($words, 1) as $word) {
            if (preg_match('/^[A-Z][a-z]+$/', $word) === 1 && !in_array(strtolower($word), $allowed, true)) {
                $titleCaseWords++;
            }
        }

        if ($titleCaseWords >= self::TITLE_CASE_THRESHOLD) {
            $this->logger->warning(
                sprintf('Heading "%s" looks like Title Case; TYPO3 documentation uses sentence case.', $heading),
                $compilerContext->getLoggerInformation(),
            );
        }
    }

    /** @return list<string> lower-cased allowed words */
    private function getAllowedWords(): array
    {
        $configured = trim($this->themeSettings->getSettings('lint_heading_allowed_words', ''));
        $extra = $configured === ''
            ? []
            : array_filter(array_map(trim(...), explode(',', $configured)), static fn(string $w): bool => $w !== '');

        return array_map(strtolower(...), array_merge(self::DEFAULT_ALLOWED_WORDS, $extra));
    }
}
