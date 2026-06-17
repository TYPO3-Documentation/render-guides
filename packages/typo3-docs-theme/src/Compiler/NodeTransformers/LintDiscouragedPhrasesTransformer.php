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
use phpDocumentor\Guides\Compiler\NodeTransformer;
use phpDocumentor\Guides\Nodes\Node;
use phpDocumentor\Guides\Nodes\SectionNode;
use Psr\Log\LoggerInterface;
use T3Docs\Typo3DocsTheme\Settings\Typo3DocsThemeSettings;

use function array_filter;
use function array_map;
use function array_unique;
use function array_values;
use function explode;
use function in_array;
use function preg_match;
use function preg_quote;
use function sprintf;
use function strtolower;
use function trim;

/**
 * Proof-of-concept documentation linter for issue #1157.
 *
 * This transformer demonstrates how a documentation-content lint rule can be
 * hooked into the render pipeline: it traverses section headings and emits a
 * warning when a heading contains a discouraged phrase (e.g. "Non-Composer
 * mode").
 *
 * Two deliberate design choices, both driven by the fact that render-guides
 * renders *third-party* extension documentation whose authors we have no
 * reliable back-channel to:
 *
 *  1. Opt-in: the rule does nothing unless the `lint` theme setting is truthy.
 *     A failing lint must never silently break or reject an extension's docs
 *     just because the rule set changed.
 *  2. Warning severity: findings are logged as warnings, so they only abort a
 *     render when the caller explicitly passes `--fail-on-log`. By default they
 *     are advisory.
 *
 * Matching is case-insensitive and bound to whole words, so a short phrase does
 * not match inside a larger word (e.g. "id" does not flag "Identifier").
 *
 * @implements NodeTransformer<SectionNode>
 */
final class LintDiscouragedPhrasesTransformer implements NodeTransformer
{
    /** @var list<string> */
    private const DEFAULT_DISCOURAGED_PHRASES = ['Non-Composer mode'];

    public function __construct(
        private readonly Typo3DocsThemeSettings $themeSettings,
        private readonly LoggerInterface $logger,
    ) {}

    public function enterNode(Node $node, CompilerContextInterface $compilerContext): Node
    {
        if (!$node instanceof SectionNode || !$this->isLintEnabled()) {
            return $node;
        }

        $heading = $node->getTitle()->toString();

        foreach ($this->getDiscouragedPhrases() as $phrase) {
            if (preg_match('/\b' . preg_quote($phrase, '/') . '\b/iu', $heading) !== 1) {
                continue;
            }
            $this->logger->warning(
                sprintf('Heading "%s" contains the discouraged phrase "%s".', $heading, $phrase),
                $compilerContext->getLoggerInformation(),
            );
        }

        return $node;
    }

    public function leaveNode(Node $node, CompilerContextInterface $compilerContext): Node
    {
        return $node;
    }

    public function supports(Node $node): bool
    {
        return $node instanceof SectionNode;
    }

    public function getPriority(): int
    {
        // Read-only pass; ordering relative to other transformers is irrelevant.
        return 1000;
    }

    private function isLintEnabled(): bool
    {
        return in_array(
            strtolower($this->themeSettings->getSettings('lint', 'false')),
            ['1', 'true', 'yes', 'on'],
            true,
        );
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
