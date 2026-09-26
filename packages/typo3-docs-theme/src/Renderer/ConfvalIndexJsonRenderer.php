<?php

declare(strict_types=1);

namespace T3Docs\Typo3DocsTheme\Renderer;

use phpDocumentor\Guides\Handlers\RenderCommand;
use phpDocumentor\Guides\Renderer\TypeRenderer;
use T3Docs\Typo3DocsTheme\ConfvalIndex\ConfvalIndex;
use T3Docs\Typo3DocsTheme\Permalinks\Permalinks;

use function json_encode;

use const JSON_PRETTY_PRINT;
use const JSON_UNESCAPED_SLASHES;
use const JSON_UNESCAPED_UNICODE;

/**
 * The options a manual documents, as one JSON file.
 *
 * "confvals.json" beside "toc.json": every ".. confval::" with its name, the
 * titles above it, the option it is nested in, its fields and the first
 * paragraph of its description. A reader looking for an option finds it by
 * name here and picks the right one by where it stands, instead of reading the
 * pages that document it -- ninety of them for the TCA reference.
 *
 * "objects.inv.json" lists the same options, but by name and address alone:
 * eight of them are called "itemsProcFunc" there, and nothing more.
 *
 * Written only by a manual that documents options.
 */
final class ConfvalIndexJsonRenderer implements TypeRenderer
{
    public const FILE_NAME = 'confvals.json';

    public function __construct(
        private readonly ConfvalIndex $confvalIndex,
        private readonly Permalinks $permalinks,
    ) {}

    public function render(RenderCommand $renderCommand): void
    {
        $projectNode = $renderCommand->getProjectNode();
        $options = $this->confvalIndex->of($renderCommand->getDocumentArray(), $projectNode);
        if ($options === []) {
            return;
        }

        $renderCommand->getDestination()->put(
            self::FILE_NAME,
            (string) json_encode(
                [
                    'project' => [
                        'title' => $projectNode->getTitle() ?? '',
                        'version' => $this->permalinks->normalizeVersion($projectNode->getVersion()),
                        // The permalink of every option at once: a consumer
                        // puts its key where the placeholder is.
                        // @see TocJsonRenderer
                        'permalink' => $this->permalinks->pattern($projectNode->getVersion()),
                    ],
                    'confvals' => $options,
                ],
                JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE,
            ),
        );
    }
}
