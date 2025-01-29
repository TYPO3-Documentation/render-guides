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

namespace T3Docs\Typo3DocsTheme\ReferenceResolvers;

use phpDocumentor\Guides\Nodes\Inline\LinkInlineNode;
use phpDocumentor\Guides\ReferenceResolvers\AnchorNormalizer;
use phpDocumentor\Guides\ReferenceResolvers\Messages;
use phpDocumentor\Guides\ReferenceResolvers\ReferenceResolver;
use phpDocumentor\Guides\RenderContext;
use phpDocumentor\Guides\Renderer\UrlGenerator\UrlGeneratorInterface;
use T3Docs\Typo3DocsTheme\Nodes\Inline\FileInlineNode;
use T3Docs\Typo3DocsTheme\Nodes\Typo3FileNode;

/**
 * Resolves references with an anchor URL.
 *
 * A link is an anchor if it starts with a hashtag
 */
final class FileReferenceResolver implements ReferenceResolver
{
    final public const PRIORITY = -200;

    public function __construct(
        private readonly AnchorNormalizer $anchorReducer,
        private readonly UrlGeneratorInterface $urlGenerator,
    ) {}

    public function resolve(LinkInlineNode $node, RenderContext $renderContext, Messages $messages): bool
    {
        if (!$node instanceof FileInlineNode || $node->getInterlinkDomain() !== '') {
            return false;
        }
        if ($node->getFileObject() === null) {
            return true;
        }

        $reducedAnchor = $this->anchorReducer->reduceAnchor($node->getFileObject()->id);
        $target = $renderContext->getProjectNode()->getInternalTarget($reducedAnchor, Typo3FileNode::LINK_TYPE);

        if ($target === null) {
            return false;
        }

        $node->setUrl($this->urlGenerator->generateCanonicalOutputUrl($renderContext, $target->getDocumentPath(), $target->getPrefix() . $target->getAnchor()));
        if ($node->getValue() === '') {
            $node->setValue($target->getTitle() ?? '');
        }

        return true;
    }

    public static function getPriority(): int
    {
        return self::PRIORITY;
    }
}
