<?php

declare(strict_types=1);

namespace T3Docs\Typo3DocsTheme\Renderer;

use phpDocumentor\Guides\Settings\SettingsManager;

use function in_array;

/**
 * The files a page was rendered to, for the JSON that points at them.
 *
 * A page of a manual on docs.typo3.org exists twice, as ".html" and as ".md",
 * and a reader -- an agent after the Markdown most of all -- should be able to
 * follow a link to the one it wants rather than having to know which extension
 * turns a bare path into a file.
 *
 * Only the forms that were rendered are named. A project can render HTML
 * alone, Markdown alone, or everything into a single page, and the table of
 * contents is written all the same; a link to a file that is not there would
 * be worse than no link.
 */
final class PageFiles
{
    /** The page-per-file output formats, and the extension each writes. */
    private const FORMATS = [
        'html' => '.html',
        'md' => '.md',
    ];

    public function __construct(
        private readonly SettingsManager $settingsManager,
    ) {}

    /**
     * @param string $path the page within the manual, without an extension
     * @return array<string, string> the page's file per format, "html" first
     */
    public function of(string $path): array
    {
        $rendered = $this->settingsManager->getProjectSettings()->getOutputFormats();

        $files = [];
        foreach (self::FORMATS as $format => $extension) {
            if (in_array($format, $rendered, true)) {
                $files[$format] = $path . $extension;
            }
        }

        return $files;
    }
}
