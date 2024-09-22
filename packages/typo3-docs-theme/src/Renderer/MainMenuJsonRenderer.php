<?php

namespace T3Docs\Typo3DocsTheme\Renderer;

use phpDocumentor\Guides\Handlers\RenderCommand;
use phpDocumentor\Guides\RenderContext;
use phpDocumentor\Guides\Renderer\TypeRenderer;
use T3Docs\Typo3DocsTheme\Nodes\Metadata\TemplateNode;
use T3Docs\Typo3DocsTheme\Renderer\NodeRenderer\MainMenuJsonDocumentRenderer;

final class MainMenuJsonRenderer implements TypeRenderer
{
    public function __construct(
        private readonly MainMenuJsonDocumentRenderer $renderer
    ) {}

    public function render(RenderCommand $renderCommand): void
    {
        $projectNode = $renderCommand->getProjectNode();

        $context = RenderContext::forProject(
            $projectNode,
            $renderCommand->getDocumentArray(),
            $renderCommand->getOrigin(),
            $renderCommand->getDestination(),
            $renderCommand->getDestinationPath(),
            'mainmenujson',
        )->withIterator($renderCommand->getDocumentIterator())
        ->withOutputFilePath('mainmenu.json');

        foreach ($renderCommand->getDocumentArray() as $key => $document) {
            $headerNodes = $document->getHeaderNodes();
            foreach ($headerNodes as $headerNode) {
                if ($headerNode instanceof TemplateNode && $headerNode->getValue() === 'mainmenu.json') {
                    $context = $context->withDocument($document);
                    $renderCommand->getDestination()->put(
                        'mainmenu.json',
                        $this->renderer->render(
                            $document,
                            $context,
                        ),
                    );
                }
            }
        }
    }
}
