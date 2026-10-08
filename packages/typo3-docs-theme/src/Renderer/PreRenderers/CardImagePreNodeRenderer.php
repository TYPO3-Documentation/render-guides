<?php

declare(strict_types=1);

namespace T3Docs\Typo3DocsTheme\Renderer\PreRenderers;

use phpDocumentor\Guides\Bootstrap\Nodes\CardNode;
use phpDocumentor\Guides\NodeRenderers\Html\PreRenderers\CollectImagesPreNodeRenderer;
use phpDocumentor\Guides\NodeRenderers\PreRenderers\PreNodeRenderer;
use phpDocumentor\Guides\Nodes\ImageNode;
use phpDocumentor\Guides\Nodes\Node;
use phpDocumentor\Guides\RenderContext;

use function ltrim;

/**
 * Copies the image of a card into the output, as an ".. image::" is copied.
 *
 * Up to phpDocumentor/guides 1.10 the template function asset() copied the
 * file it was given. Since 1.11 it only builds the address, and only images
 * of an image node are copied, before they are rendered. A card renders its
 * image itself, with asset(), from the path its ".. card-image::" names, so
 * the image was missing from the output.
 *
 * The path is taken from the root of the documentation, as asset() takes it
 * when it builds the address.
 */
final class CardImagePreNodeRenderer implements PreNodeRenderer
{
    public function __construct(
        private readonly CollectImagesPreNodeRenderer $collectImages,
    ) {}

    public function supports(Node $node): bool
    {
        return $node instanceof CardNode && $node->getCardImage() !== null;
    }

    public function execute(Node $node, RenderContext $renderContext): Node
    {
        $cardImage = $node instanceof CardNode ? $node->getCardImage() : null;
        if ($cardImage !== null) {
            $this->collectImages->execute(new ImageNode('/' . ltrim($cardImage->getPlainContent(), '/')), $renderContext);
        }

        return $node;
    }
}
