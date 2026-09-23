<?php

namespace T3Docs\GuidesExtension\Renderer;

use phpDocumentor\Guides\Handlers\RenderCommand;
use phpDocumentor\Guides\RenderContext;
use phpDocumentor\Guides\Renderer\TypeRenderer;
use phpDocumentor\Guides\TemplateRenderer;

final class SinglePageRenderer implements TypeRenderer
{
    public const OUTPUT_FILE = 'singlehtml/Index.html';

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
            'singlepage',
        )->withIterator($renderCommand->getDocumentIterator())
        ->withOutputFilePath(self::OUTPUT_FILE);

        $context->getDestination()->put(
            $renderCommand->getDestinationPath() . '/' . self::OUTPUT_FILE,
            $this->renderer->renderTemplate(
                $context,
                'structure/singlepage.html.twig',
                [
                    'project' => $projectNode,
                    'documents' =>  $renderCommand->getDocumentIterator(),
                ],
            ),
        );
    }
}
