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

namespace T3Docs\GuidesExtension\Renderer\UrlGenerator;

use phpDocumentor\Guides\ReferenceResolvers\DocumentNameResolverInterface;
use phpDocumentor\Guides\RenderContext;
use phpDocumentor\Guides\Renderer\UrlGenerator\AbstractUrlGenerator;
use phpDocumentor\Guides\Renderer\UrlGenerator\RelativeUrlGenerator;

final class SingleHtmlUrlGenerator extends AbstractUrlGenerator
{
    public function __construct(
        private readonly RelativeUrlGenerator $relativeUrlGenerator,
        DocumentNameResolverInterface $documentNameResolver,
    ) {
        parent::__construct($documentNameResolver);
    }
    public function generateInternalPathFromRelativeUrl(
        RenderContext $renderContext,
        string $canonicalUrl,
    ): string {
        $parsedUrl = parse_url($canonicalUrl);
        $anchor = $parsedUrl['fragment'] ?? '';
        $fileInfo = pathinfo($canonicalUrl);
        $dirname = $fileInfo['dirname'] ?? '.';
        if ($dirname === '.') {
            // if no directory is set $fileInfo['dirname'] returns "."
            $dirname = '';
        } else {
            $dirname = $dirname . '/';
        }
        $filename = $fileInfo['filename'] ?? '';
        if ($renderContext->getProjectNode()->findDocumentEntry($dirname . $filename) === null) {
            // this is not a link to a rendered document, therefore to an asset
            return $this->relativeUrlGenerator->generateInternalPathFromRelativeUrl($renderContext, $canonicalUrl);
        }
        return '#' . $anchor;
    }
}
