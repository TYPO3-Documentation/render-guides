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
use phpDocumentor\Guides\Renderer\UrlGenerator\AbsoluteUrlGenerator;
use phpDocumentor\Guides\Renderer\UrlGenerator\AbstractUrlGenerator;
use phpDocumentor\Guides\Renderer\UrlGenerator\RelativeUrlGenerator;
use phpDocumentor\Guides\Settings\SettingsManager;

final class RenderOutputUrlGenerator extends AbstractUrlGenerator
{
    public function __construct(
        private readonly RelativeUrlGenerator $relativeUrlGenerator,
        private readonly SingleHtmlUrlGenerator $singleHtmlUrlGenerator,
        DocumentNameResolverInterface $documentNameResolver,
    ) {
        parent::__construct($documentNameResolver);
    }

    public function generateInternalPathFromRelativeUrl(
        RenderContext $renderContext,
        string $canonicalUrl,
    ): string {
        if ($renderContext->getOutputFormat() === 'singlepage') {
            return $this->singleHtmlUrlGenerator->generateInternalPathFromRelativeUrl($renderContext, $canonicalUrl);
        }

        return $this->relativeUrlGenerator->generateInternalPathFromRelativeUrl($renderContext, $canonicalUrl);
    }
}
