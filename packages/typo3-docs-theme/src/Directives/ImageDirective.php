<?php

declare(strict_types=1);

namespace T3Docs\Typo3DocsTheme\Directives;

use phpDocumentor\Guides\Compiler\CompilerContextInterface;
use phpDocumentor\Guides\Nodes\Node;
use phpDocumentor\Guides\ReferenceResolvers\DocumentNameResolverInterface;
use phpDocumentor\Guides\RestructuredText\Directives\Attributes;
use phpDocumentor\Guides\RestructuredText\Directives\BaseDirective;
use phpDocumentor\Guides\RestructuredText\Directives\ImageDirective as BaseImageDirective;
use phpDocumentor\Guides\RestructuredText\Nodes\DirectiveNode;
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
 * The deprecation warning additionally mentions `:class: float-start` /
 * `:class: float-end`: docutils restricts `:align:` on substitution images
 * (|name|) to top/middle/bottom, so the class is the portable choice there.
 *
 * Like the upstream directive it creates its node while compiling, from the
 * DirectiveNode the parser leaves: the upstream directive no longer creates
 * an image in process(), only a generic node that renders its path as text.
 * The options the directive does not consume itself, ":zoom:" and the like,
 * reach the image the same way. @see DirectiveProcessPass
 *
 * @see BaseImageDirective (upstream, composed)
 * @see https://github.com/phpDocumentor/guides/issues/1303 (final removal request)
 * @see FigureDirective (same float class rewriting for figures)
 */
#[Attributes\Directive(name: 'image')]
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

    public function createNode(DirectiveNode $directiveNode, CompilerContextInterface $compilerContext): Node
    {
        // Detect and rewrite legacy float classes before delegating to upstream
        // See also: figure.html.twig / image.html.twig alignMap for :align: option mapping
        $directive = $directiveNode->getDirective();
        if ($directive->hasOption('class')) {
            $classValue = $directive->getOption('class')->getValue();
            if (is_string($classValue) && $this->hasLegacyFloatClass($classValue)) {
                $this->logger->warning(
                    'Using `:class: float-left` / `:class: float-right` is deprecated. '
                    . 'Use `:align: left` / `:align: right` or `:class: float-start` / `:class: float-end` instead.',
                    $directiveNode->getSourceLocation()->toLoggerInformation(),
                );
                $directive->addOption(new DirectiveOption('class', $this->rewriteLegacyFloatClasses($classValue)));
            }
        }

        return $this->inner->createNode($directiveNode, $compilerContext);
    }
}
