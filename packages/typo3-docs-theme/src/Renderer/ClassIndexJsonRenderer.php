<?php

declare(strict_types=1);

namespace T3Docs\Typo3DocsTheme\Renderer;

use phpDocumentor\Guides\Handlers\RenderCommand;
use phpDocumentor\Guides\Renderer\TypeRenderer;
use T3Docs\Typo3DocsTheme\ClassIndex\ClassIndex;
use T3Docs\Typo3DocsTheme\Permalinks\Permalinks;

use function json_encode;

use const JSON_PRETTY_PRINT;
use const JSON_UNESCAPED_SLASHES;
use const JSON_UNESCAPED_UNICODE;

/**
 * Where a manual speaks of each PHP class, as one JSON file.
 *
 * "classes.json" beside "toc.json": every class the manual names, and for each
 * of them the places that name it -- the page, the anchor of the section, and
 * whether the class is mentioned in the text, imported by an example, or
 * documented there.
 *
 * It answers the question a reader of the API documentation has and no
 * rendered artifact could answer so far: where is this class explained, and
 * what else says anything about it. "objects.inv.json" lists what a manual
 * documents, which is the definitions alone, and nothing has listed the
 * mentions.
 */
final class ClassIndexJsonRenderer implements TypeRenderer
{
    public function __construct(
        private readonly ClassIndex $classIndex,
        private readonly Permalinks $permalinks,
    ) {}

    public function render(RenderCommand $renderCommand): void
    {
        $projectNode = $renderCommand->getProjectNode();

        $index = [
            'project' => [
                'title' => $projectNode->getTitle() ?? '',
                'version' => $this->permalinks->normalizeVersion($projectNode->getVersion()),
                // The permalink of every place at once: a consumer puts an
                // anchor where the placeholder is. @see TocJsonRenderer
                'permalink' => $this->permalinks->pattern($projectNode->getVersion()),
            ],
            'classes' => $this->classIndex->of($renderCommand->getDocumentArray()),
        ];

        $renderCommand->getDestination()->put(
            'classes.json',
            (string) json_encode($index, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
        );
    }
}
