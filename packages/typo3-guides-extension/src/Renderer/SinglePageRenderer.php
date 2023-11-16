<?php

namespace T3Docs\GuidesExtension\Renderer;

use phpDocumentor\Guides\Handlers\RenderCommand;
use phpDocumentor\Guides\RenderContext;
use phpDocumentor\Guides\Renderer\TypeRenderer;
use phpDocumentor\Guides\TemplateRenderer;

final class SinglePageRenderer implements TypeRenderer
{
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
        );

        $context->getDestination()->put(
            $renderCommand->getDestinationPath() . '/singlehtml/Index.html',
            $this->renderer->renderTemplate(
                $context,
                'structure/singlepage.html.twig',
                [
                    'project' => $projectNode,
                    'documents' =>  $renderCommand->getDocumentArray(),
                ],
            ),
        );
    }
}
