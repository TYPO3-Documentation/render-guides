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
use phpDocumentor\Guides\RestructuredText\Parser\Productions\Rule;

use function dirname;
use function in_array;
use function is_string;
use function preg_replace;
use function trim;

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
    private const VALID_ZOOM_MODES = ['lightbox', 'gallery', 'inline', 'lens'];

    /** @param Rule<CollectionNode> $startingRule */
    public function __construct(
        private readonly DocumentNameResolverInterface $documentNameResolver,
        private readonly Rule $startingRule,
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
        // Strip float classes from the inner image - floating should only
        // apply to the <figure> element to keep the caption below the image
        $imageClass = isset($scalarOptions['class']) && is_string($scalarOptions['class'])
            ? $scalarOptions['class']
            : null;
        if ($imageClass !== null) {
            $imageClass = trim((string) preg_replace('/\bfloat-(left|right)\b/', '', $imageClass)) ?: null;
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

        $figureNode = new FigureNode($image, new CollectionNode($collectionNode->getChildren()));

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

        // Remove options that are already handled by the image node
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
