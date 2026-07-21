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

use phpDocumentor\Guides\Nodes\CollectionNode;
use phpDocumentor\Guides\Nodes\FigureNode;
use phpDocumentor\Guides\Nodes\ImageNode;
use phpDocumentor\Guides\Nodes\Node;
use phpDocumentor\Guides\ReferenceResolvers\DocumentNameResolverInterface;
use phpDocumentor\Guides\RestructuredText\Directives\BaseDirective;
use phpDocumentor\Guides\RestructuredText\Parser\BlockContext;
use phpDocumentor\Guides\RestructuredText\Parser\Directive;
use phpDocumentor\Guides\RestructuredText\Parser\DirectiveOption;
use phpDocumentor\Guides\RestructuredText\Parser\Productions\Rule;
use Psr\Log\LoggerInterface;

use function array_filter;
use function dirname;
use function explode;
use function implode;
use function in_array;
use function is_string;

/**
 * Renders a figure with optional zoom functionality.
 *
 * Example:
 *
 * .. figure:: image.jpg
 *      :width: 100
 *      :alt: An image
 *      :zoom: lightbox
 *      :zoom-factor: 3
 *
 *      Here is an awesome caption
 *
 * Supported zoom modes:
 * - lightbox - Click to open in dialog overlay
 * - gallery - Click to open with wheel zoom and navigation
 * - inline - Scroll wheel zoom directly on image
 * - lens - Magnifier lens follows cursor
 *
 * Additional options:
 * - :gallery: - Group name for gallery navigation
 * - :zoom-indicator: - Show/hide zoom icon (default: true)
 * - :zoom-factor: - Magnification factor for lens mode (default: 2)
 */
final class FigureDirective extends BaseDirective
{
    use RewritesLegacyFloatClasses;

    private const VALID_ZOOM_MODES = ['lightbox', 'gallery', 'inline', 'lens'];

    /** @param Rule<CollectionNode> $startingRule */
    public function __construct(
        private readonly DocumentNameResolverInterface $documentNameResolver,
        private readonly Rule $startingRule,
        private readonly LoggerInterface $logger,
    ) {}

    public function getName(): string
    {
        return 'figure';
    }

    public function process(
        BlockContext $blockContext,
        Directive $directive,
    ): Node|null {
        $collectionNode = $this->startingRule->apply($blockContext);

        if ($collectionNode === null) {
            return null;
        }

        $scalarOptions = $this->optionsToArray($directive->getOptions());

        // Create the image node with standard options
        $image = new ImageNode($this->documentNameResolver->absoluteUrl(
            dirname($blockContext->getDocumentParserContext()->getContext()->getCurrentAbsolutePath()),
            $directive->getData(),
        ));
        // Handle float classes on the figure element
        $figureClass = isset($scalarOptions['class']) && is_string($scalarOptions['class'])
            ? $scalarOptions['class']
            : null;

        if ($figureClass !== null) {
            // Detect and rewrite legacy float-left/float-right to float-start/float-end
            // See also: figure.html.twig / image.html.twig alignMap for :align: option mapping
            if ($this->hasLegacyFloatClass($figureClass)) {
                $this->logger->warning(
                    'Using `:class: float-left` / `:class: float-right` is deprecated. '
                    . 'Use `:align: left` / `:align: right` instead.',
                    $blockContext->getLoggerInformation(),
                );
                $figureClass = $this->rewriteLegacyFloatClasses($figureClass);
                // Update the raw directive option so that DirectiveRule::postProcessNode
                // uses the rewritten class when calling setClasses() on the node
                $directive->addOption(new DirectiveOption('class', $figureClass));
            }
        }

        // Strip float classes from the inner image — floating should only apply
        // to the <figure> element to keep the caption below the image.
        // Non-float classes (e.g. with-shadow) are still propagated to the
        // inner <img> so they can style the image itself.
        if ($figureClass !== null) {
            $imageClasses = array_filter(
                explode(' ', $figureClass),
                static fn(string $class): bool => !in_array($class, ['float-start', 'float-end'], true),
            );
            $imageClass = $imageClasses !== [] ? implode(' ', $imageClasses) : null;
        } else {
            $imageClass = null;
        }

        $image = $image->withOptions([
            'width' => $scalarOptions['width'] ?? null,
            'height' => $scalarOptions['height'] ?? null,
            'alt' => $scalarOptions['alt'] ?? null,
            'scale' => $scalarOptions['scale'] ?? null,
            'target' => $scalarOptions['target'] ?? null,
            'class' => $imageClass,
            'name' => $scalarOptions['name'] ?? null,
        ]);

        $figureNode = new FigureNode($image, new CollectionNode(array_values($collectionNode->getChildren())));

        // Build filtered options - copy all options but validate zoom mode
        // We must set all options ourselves because DirectiveRule::postProcessNode
        // will merge raw options with node options, and we need our filtered
        // zoom value to take precedence
        $filteredOptions = $scalarOptions;

        // Validate zoom mode - set to null for invalid values
        // We must keep the key with null value so it overrides the raw option in postProcessNode
        if (isset($filteredOptions['zoom']) && !in_array($filteredOptions['zoom'], self::VALID_ZOOM_MODES, true)) {
            $filteredOptions['zoom'] = null;
        }

        // Remove options that are already handled by the image node or by
        // DirectiveRule::postProcessNode (class is handled via setClasses())
        unset(
            $filteredOptions['width'],
            $filteredOptions['height'],
            $filteredOptions['alt'],
            $filteredOptions['scale'],
            $filteredOptions['target'],
            $filteredOptions['class'],
            $filteredOptions['name'],
        );

        if (!empty($filteredOptions)) {
            $figureNode = $figureNode->withOptions($filteredOptions);
        }

        return $figureNode;
    }
}
