<?php

declare(strict_types=1);

namespace T3Docs\Typo3DocsThemeMd\Renderer;

use phpDocumentor\Guides\Handlers\RenderCommand;
use phpDocumentor\Guides\RenderContext;
use phpDocumentor\Guides\Renderer\TypeRenderer;
use phpDocumentor\Guides\TemplateRenderer;

/**
 * Renders the whole project into a single Markdown file.
 *
 * The Markdown counterpart of SinglePageRenderer: where the "md" format writes
 * one file per document, this writes "singlemd/Index.md" with every document
 * concatenated, so the manual can be handed to a tool that wants the entire
 * content in one piece.
 *
 * Not part of any default output format. It is reached through the
 * "--single-markdown" option of the run command, because the file is only
 * useful to somebody rendering locally for their own tooling -- publishing it
 * would double the output of every manual on docs.typo3.org for a file nobody
 * follows a link to.
 */
final class SingleMarkdownRenderer implements TypeRenderer
{
    private const OUTPUT_FILE = 'singlemd/Index.md';

    public function __construct(private readonly TemplateRenderer $renderer) {}

    public function render(RenderCommand $renderCommand): void
    {
        $projectNode = $renderCommand->getProjectNode();

        $context = RenderContext::forProject(
            $projectNode,
            $renderCommand->getDocumentArray(),
            $renderCommand->getOrigin(),
            $renderCommand->getDestination(),
            $renderCommand->getDestinationPath(),
            'singlemd',
        )->withIterator($renderCommand->getDocumentIterator())
        ->withOutputFilePath(self::OUTPUT_FILE);

        $context->getDestination()->put(
            $renderCommand->getDestinationPath() . '/' . self::OUTPUT_FILE,
            $this->renderer->renderTemplate(
                $context,
                'structure/singlepage.md.twig',
                [
                    'project' => $projectNode,
                    'documents' => $renderCommand->getDocumentIterator(),
                ],
            ),
        );
    }
}
