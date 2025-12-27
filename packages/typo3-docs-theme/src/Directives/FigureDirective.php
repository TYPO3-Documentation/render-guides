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
 *
 *      Here is an awesome caption
 *
 * Supported zoom values:
 * - lightbox (default) - Dialog overlay
 * - gallery - PhotoSwipe-style with wheel zoom
 * - inline - In-place zoom
 * - lens - Magnifier lens
 * - css-only - No JS fallback
 * - none or false - Disable zoom
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
        if (isset($scalarOptions['zoom'])) {
            $zoomOptions['zoom'] = $scalarOptions['zoom'];
        }
        if (isset($scalarOptions['zoom-indicator'])) {
            $zoomOptions['zoom-indicator'] = $scalarOptions['zoom-indicator'];
        }
        if (isset($scalarOptions['gallery'])) {
            $zoomOptions['gallery'] = $scalarOptions['gallery'];
        }
        if (!empty($zoomOptions)) {
            $figureNode = $figureNode->withOptions($zoomOptions);
        }

        return $figureNode;
    }
}
