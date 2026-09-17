<?php

declare(strict_types=1);

namespace T3Docs\Typo3DocsTheme\Renderer;

use phpDocumentor\Guides\Handlers\RenderCommand;
use phpDocumentor\Guides\Renderer\TypeRenderer;
use T3Docs\Typo3DocsTheme\Changelog\ChangelogEntry;
use T3Docs\Typo3DocsTheme\Settings\Typo3DocsThemeSettings;

use function json_encode;
use function ksort;
use function sprintf;
use function usort;

use const JSON_PRETTY_PRINT;
use const JSON_UNESCAPED_SLASHES;
use const JSON_UNESCAPED_UNICODE;

/**
 * A machine-readable index of the Changelog, one file per major release.
 *
 * "Changelog-14.json" beside "Changelog-14.html" and its Markdown: the same
 * entries the overview page lists, with the permalink of each entry, its file,
 * its title, the kind of change, the issue and the tags.
 *
 * The overview page links entries by permalink, which costs a redirect per
 * entry -- 445 of them for v14 -- for anybody walking the release rather than
 * reading one page. This is the list in one request, and it carries what the
 * page cannot: an entry's file, so a reader can go straight to the Markdown.
 *
 * Written only for the Core Changelog, recognised by its interlink shortcode,
 * and written by the theme rather than asked for in the manual's guides.xml --
 * that file lives in typo3/cms-core, and this should not need a Core patch to
 * exist.
 */
final class ChangelogJsonRenderer implements TypeRenderer
{
    private const PERMALINK_BASE = 'https://docs.typo3.org/permalink/';

    public function __construct(
        private readonly ChangelogEntry $changelogEntry,
        private readonly Typo3DocsThemeSettings $themeSettings,
    ) {}

    public function render(RenderCommand $renderCommand): void
    {
        $shortcode = $this->themeSettings->getSettings('interlink_shortcode');
        if ($shortcode !== 'changelog') {
            return;
        }

        $byMajor = [];
        foreach ($renderCommand->getDocumentArray() as $document) {
            $entry = $this->changelogEntry->describe($document);
            if ($entry === null) {
                continue;
            }

            // The URL that names the entry wherever its file ends up. No
            // "@version" here: the Changelog is deployed to main only, so its
            // permalinks never carry one.
            $permalink = $entry['anchor'] === ''
                ? ''
                : self::PERMALINK_BASE . $shortcode . ':' . $entry['anchor'];

            $byMajor[$entry['typo3-major']][] = ['permalink' => $permalink, ...$entry];
        }

        ksort($byMajor);

        foreach ($byMajor as $major => $entries) {
            // Newest first within a release, the way the overview page reads,
            // and by issue so two entries of the same version keep a stable
            // order between runs.
            usort($entries, static function (array $a, array $b): int {
                return [$b['typo3-version'], $b['issue']] <=> [$a['typo3-version'], $a['issue']];
            });

            $renderCommand->getDestination()->put(
                sprintf('Changelog-%d.json', $major),
                (string) json_encode(
                    ['major' => $major, 'entries' => $entries],
                    JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE,
                ),
            );
        }
    }
}
