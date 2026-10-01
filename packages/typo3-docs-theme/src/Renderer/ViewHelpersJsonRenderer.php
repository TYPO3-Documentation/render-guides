<?php

declare(strict_types=1);

namespace T3Docs\Typo3DocsTheme\Renderer;

use phpDocumentor\Guides\Handlers\RenderCommand;
use phpDocumentor\Guides\Renderer\TypeRenderer;
use T3Docs\Typo3DocsTheme\Permalinks\Permalinks;
use T3Docs\Typo3DocsTheme\ViewHelperIndex\ViewHelperIndex;

use function json_encode;

use const JSON_PRETTY_PRINT;
use const JSON_UNESCAPED_SLASHES;
use const JSON_UNESCAPED_UNICODE;

/**
 * The ViewHelpers a manual documents, as one JSON file.
 *
 * "viewhelpers.json" beside "toc.json": every ViewHelper by the name a
 * template writes, with what the file describing it says -- class, namespace,
 * documentation, doc tags, arguments -- and the page, anchor and permalink
 * where the manual shows it and each of its arguments.
 *
 * "objects.inv.json" lists the ViewHelpers too, but by a key made from the
 * class and a title without the prefix of the namespace, so "f:format.html"
 * cannot be looked up there, and nothing is said about it.
 *
 * Written only by a manual that documents ViewHelpers. @see ViewHelperIndex
 */
final class ViewHelpersJsonRenderer implements TypeRenderer
{
    public const FILE_NAME = 'viewhelpers.json';

    public function __construct(
        private readonly ViewHelperIndex $viewHelperIndex,
        private readonly Permalinks $permalinks,
    ) {}

    public function render(RenderCommand $renderCommand): void
    {
        $projectNode = $renderCommand->getProjectNode();
        $viewHelpers = $this->viewHelperIndex->of($renderCommand->getDocumentArray(), $projectNode->getVersion());
        if ($viewHelpers === []) {
            return;
        }

        $renderCommand->getDestination()->put(
            self::FILE_NAME,
            (string) json_encode(
                [
                    'project' => [
                        'title' => $projectNode->getTitle() ?? '',
                        'version' => $this->permalinks->normalizeVersion($projectNode->getVersion()),
                        'permalink' => $this->permalinks->pattern($projectNode->getVersion()),
                    ],
                    'viewhelpers' => $viewHelpers,
                ],
                JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE,
            ),
        );
    }
}
