<?php

declare(strict_types=1);

namespace T3Docs\Typo3DocsThemeMd\Renderer;

use phpDocumentor\Guides\Renderer\BaseTypeRenderer;

/**
 * Renders the document AST to Markdown.
 *
 * Registered as a type renderer for the "md" output format, which is what makes
 * RenderContext write "Feature.md" beside "Feature.html": the output path is
 * built as the document file path plus the output format name.
 */
final class MdRenderer extends BaseTypeRenderer {}
