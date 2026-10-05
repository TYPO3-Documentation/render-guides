<?php

namespace T3Docs\Typo3DocsTheme\Renderer;

use phpDocumentor\Guides\Handlers\RenderCommand;
use phpDocumentor\Guides\Nodes\CompoundNode;
use phpDocumentor\Guides\Nodes\Node;
use phpDocumentor\Guides\RenderContext;
use phpDocumentor\Guides\Renderer\TypeRenderer;
use T3Docs\Typo3DocsTheme\ManualsIndex\ManualsIndex;
use T3Docs\Typo3DocsTheme\Nodes\MainMenuJsonNode;
use T3Docs\Typo3DocsTheme\Nodes\Metadata\TemplateNode;
use T3Docs\Typo3DocsTheme\Renderer\NodeRenderer\MainMenuJsonDocumentRenderer;

use function json_encode;

use const JSON_PRETTY_PRINT;
use const JSON_UNESCAPED_SLASHES;
use const JSON_UNESCAPED_UNICODE;

/**
 * The main menu of docs.typo3.org, "mainmenu.json", and beside it the manuals
 * that menu lists, "manuals.json".
 *
 * Every manual publishes indexes of its own beside its pages, but only the
 * menu knows which manuals there are. "manuals.json" names each one by its
 * interlink shortcode, with the address of every version the menu offers:
 * where its "toc.json", "objects.inv.json" and the other indexes are.
 * @see ManualsIndex
 */
final class MainMenuJsonRenderer implements TypeRenderer
{
    public const MANUALS_FILE_NAME = 'manuals.json';

    /**
     * The indexes a manual publishes beside its pages, and which manuals do:
     * a consumer appends a name to the base of a version.
     */
    private const FILES = [
        'llms.txt' => 'every manual',
        'toc.json' => 'every manual',
        'objects.inv.json' => 'every manual',
        'classes.json' => 'every manual',
        'confvals.json' => 'a manual that documents options',
        'files.json' => 'a manual that defines files',
        'viewhelpers.json' => 'a manual that documents ViewHelpers',
    ];

    public function __construct(
        private readonly MainMenuJsonDocumentRenderer $renderer,
        private readonly ManualsIndex $manualsIndex,
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

                    $menu = $this->menu($document);
                    if ($menu !== null) {
                        $renderCommand->getDestination()->put(
                            self::MANUALS_FILE_NAME,
                            (string) json_encode(
                                ['manuals' => $this->manualsIndex->of($menu, $context), 'files' => self::FILES],
                                JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE,
                            ),
                        );
                    }
                }
            }
        }
    }

    private function menu(Node $node): ?MainMenuJsonNode
    {
        if ($node instanceof MainMenuJsonNode) {
            return $node;
        }
        if (!$node instanceof CompoundNode) {
            return null;
        }
        foreach ($node->getChildren() as $child) {
            $menu = $child instanceof Node ? $this->menu($child) : null;
            if ($menu !== null) {
                return $menu;
            }
        }

        return null;
    }
}
