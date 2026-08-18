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

namespace T3Docs\Typo3DocsTheme\EventListeners;

use phpDocumentor\Guides\Event\PreParseDocument;
use Psr\Log\LoggerInterface;
use T3Docs\Typo3DocsTheme\Lint\SourceLintRule;
use T3Docs\Typo3DocsTheme\Settings\Typo3DocsThemeSettings;

/**
 * Runs the opt-in source-level lint rules (#1157) against the raw document
 * source before it is parsed.
 *
 * Like the AST heading rules, this is gated on the `lint` setting (default off)
 * and only ever emits warnings, so it never breaks a third-party extension's
 * render unless the caller explicitly enables linting and uses --fail-on-log.
 */
final class SourceLintListener
{
    /** @param iterable<SourceLintRule> $rules */
    public function __construct(
        private readonly Typo3DocsThemeSettings $themeSettings,
        private readonly LoggerInterface $logger,
        private readonly iterable $rules,
    ) {}

    public function __invoke(PreParseDocument $event): void
    {
        if (!$this->themeSettings->isEnabled('lint')) {
            return;
        }

        $context = ['rst-file' => $event->getFileName()];
        $contents = $event->getContents();

        foreach ($this->rules as $rule) {
            foreach ($rule->lint($contents) as $warning) {
                $this->logger->warning($warning, $context);
            }
        }
    }
}
