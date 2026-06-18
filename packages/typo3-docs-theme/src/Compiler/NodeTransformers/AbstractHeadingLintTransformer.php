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

/**
 * Base class for opt-in, per-section heading lint rules (see #1157).
 *
 * The base owns the cross-cutting concerns shared by every heading rule:
 * the opt-in gate (the `lint` theme setting, default off), the SectionNode
 * filtering and the warning channel. Subclasses only implement checkSection().
 *
 * Linting is intentionally opt-in and warning-only: render-guides renders
 * third-party extension documentation whose authors we cannot reliably reach,
 * so a rule must never break or reject a render unless the caller explicitly
 * enables linting and passes --fail-on-log.
 *
 * @implements NodeTransformer<SectionNode>
 */
abstract class AbstractHeadingLintTransformer implements NodeTransformer
{
    public function __construct(
        protected readonly Typo3DocsThemeSettings $themeSettings,
        protected readonly LoggerInterface $logger,
    ) {}

    final public function enterNode(Node $node, CompilerContextInterface $compilerContext): Node
    {
        if ($node instanceof SectionNode && $this->isLintEnabled()) {
            $this->checkSection($node, $compilerContext);
        }

        return $node;
    }

    final public function leaveNode(Node $node, CompilerContextInterface $compilerContext): Node
    {
        return $node;
    }

    final public function supports(Node $node): bool
    {
        return $node instanceof SectionNode;
    }

    final public function getPriority(): int
    {
        // Read-only pass; ordering relative to other transformers is irrelevant.
        return 1000;
    }

    abstract protected function checkSection(SectionNode $section, CompilerContextInterface $compilerContext): void;

    protected function isLintEnabled(): bool
    {
        return $this->themeSettings->isEnabled('lint');
    }
}
