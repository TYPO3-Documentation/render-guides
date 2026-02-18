<?php

declare(strict_types=1);

namespace T3Docs\Typo3DocsTheme\Directives;

use phpDocumentor\Guides\Nodes\Node;
use phpDocumentor\Guides\ReferenceResolvers\DocumentNameResolverInterface;
use phpDocumentor\Guides\RestructuredText\Directives\BaseDirective;
use phpDocumentor\Guides\RestructuredText\Directives\ImageDirective as BaseImageDirective;
use phpDocumentor\Guides\RestructuredText\Parser\BlockContext;
use phpDocumentor\Guides\RestructuredText\Parser\Directive;
use phpDocumentor\Guides\RestructuredText\Parser\DirectiveOption;
use Psr\Log\LoggerInterface;

use function is_string;

/**
 * Decorates the upstream ImageDirective to detect and rewrite deprecated float class names.
 *
 * Intercepts `:class: float-left` / `:class: float-right` and rewrites them to
 * `float-start` / `float-end` (Bootstrap 5) before delegating to the upstream directive,
 * emitting a deprecation warning.
 *
 * Uses composition instead of inheritance because the upstream class is declared final.
 * The upstream instance is created internally to avoid Symfony service decoration
 * side effects with tagged directive services.
 *
 * The deprecation warning additionally mentions `float-start`/`float-end` as
 * alternatives because substitution images (|name|) do not support the `:align:`
 * option — only `:class:` is available.
 *
 * Note: process() delegates to $this->inner->process() which internally calls
 * BaseDirective::process() including withKeepExistingOptions(). We intentionally
 * skip the outer BaseDirective::process() to avoid double-applying options.
 *
 * @see BaseImageDirective (upstream, composed)
 * @see https://github.com/phpDocumentor/guides/issues/1303 (final removal request)
 * @see FigureDirective (same float class rewriting for figures)
 */
final class ImageDirective extends BaseDirective
{
    use RewritesLegacyFloatClasses;

    private readonly BaseImageDirective $inner;

    public function __construct(
        DocumentNameResolverInterface $documentNameResolver,
        private readonly LoggerInterface $logger,
    ) {
        $this->inner = new BaseImageDirective($documentNameResolver);
    }

    public function getName(): string
    {
        return 'image';
    }

    public function process(
        BlockContext $blockContext,
        Directive $directive,
    ): Node|null {
        // Detect and rewrite legacy float classes before delegating to upstream
        // See also: figure.html.twig / image.html.twig alignMap for :align: option mapping
        if ($directive->hasOption('class')) {
            $classValue = $directive->getOption('class')->getValue();
            if (is_string($classValue) && $this->hasLegacyFloatClass($classValue)) {
                $this->logger->warning(
                    'Using `:class: float-left` / `:class: float-right` is deprecated. '
                    . 'Use `:align: left` / `:align: right` instead, or `float-start` / `float-end` for substitution images.',
                    $blockContext->getLoggerInformation(),
                );
                $directive->addOption(new DirectiveOption('class', $this->rewriteLegacyFloatClasses($classValue)));
            }
        }

        return $this->inner->process($blockContext, $directive);
    }
}
