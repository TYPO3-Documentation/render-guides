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
use phpDocumentor\Guides\RestructuredText\Directives\SubDirective;
use phpDocumentor\Guides\RestructuredText\Parser\BlockContext;
use phpDocumentor\Guides\RestructuredText\Parser\Directive;
use phpDocumentor\Guides\RestructuredText\Parser\Productions\Rule;

use function dirname;

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
final class FigureDirective extends SubDirective
{
    public function __construct(
        private readonly DocumentNameResolverInterface $documentNameResolver,
        protected Rule $startingRule,
    ) {
        parent::__construct($startingRule);
    }

    public function getName(): string
    {
        return 'figure';
    }

    /** {@inheritDoc}
     *
     * @param Directive $directive
     */
    protected function processSub(
        BlockContext $blockContext,
        CollectionNode $collectionNode,
        Directive $directive,
    ): Node|null {
        $image = new ImageNode($this->documentNameResolver->absoluteUrl(
            dirname($blockContext->getDocumentParserContext()->getContext()->getCurrentAbsolutePath()),
            $directive->getData(),
        ));
        $scalarOptions = $this->optionsToArray($directive->getOptions());
        $image = $image->withOptions([
            'width' => $scalarOptions['width'] ?? null,
            'height' => $scalarOptions['height'] ?? null,
            'alt' => $scalarOptions['alt'] ?? null,
            'scale' => $scalarOptions['scale'] ?? null,
            'target' => $scalarOptions['target'] ?? null,
            'class' => $scalarOptions['class'] ?? null,
            'name' => $scalarOptions['name'] ?? null,
            'align' => $scalarOptions['align'] ?? null,
        ]);

        $figureNode = new FigureNode($image, new CollectionNode($collectionNode->getChildren()));

        // Add zoom options to the figure node if specified
        $zoomOptions = [];
        $validZoomModes = ['lightbox', 'gallery', 'inline', 'lens'];
        if (isset($scalarOptions['zoom']) && in_array($scalarOptions['zoom'], $validZoomModes, true)) {
            $zoomOptions['zoom'] = $scalarOptions['zoom'];
        }
        if (isset($scalarOptions['zoom-indicator'])) {
            $zoomOptions['zoom-indicator'] = $scalarOptions['zoom-indicator'];
        }
        if (isset($scalarOptions['gallery'])) {
            $zoomOptions['gallery'] = $scalarOptions['gallery'];
        }
        if (isset($scalarOptions['zoom-factor'])) {
            $zoomOptions['zoom-factor'] = $scalarOptions['zoom-factor'];
        }
        if (!empty($zoomOptions)) {
            $figureNode = $figureNode->withOptions($zoomOptions);
        }

        return $figureNode;
    }
}
