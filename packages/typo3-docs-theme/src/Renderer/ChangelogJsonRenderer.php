<?php

declare(strict_types=1);

namespace T3Docs\Typo3DocsTheme\Renderer;

use phpDocumentor\Guides\Handlers\RenderCommand;
use phpDocumentor\Guides\Renderer\TypeRenderer;
use T3Docs\Typo3DocsTheme\Changelog\ChangelogEntry;
use T3Docs\Typo3DocsTheme\ClassIndex\ClassIndex;
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
 * its title, the kind of change, the issue, the tags, and the PHP classes it
 * speaks of.
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
 *
 * @phpstan-import-type Place from ClassIndex
 */
final class ChangelogJsonRenderer implements TypeRenderer
{
    private const PERMALINK_BASE = 'https://docs.typo3.org/permalink/';

    public function __construct(
        private readonly ChangelogEntry $changelogEntry,
        private readonly Typo3DocsThemeSettings $themeSettings,
        private readonly PageFiles $pageFiles,
        private readonly ClassIndex $classIndex,
    ) {}

    public function render(RenderCommand $renderCommand): void
    {
        $shortcode = $this->themeSettings->getSettings('interlink_shortcode');
        if ($shortcode !== 'changelog') {
            return;
        }

        $classesByPath = $this->classesByPath($renderCommand);

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

            // The entry's files next to its path: the permalink leads to the
            // HTML, and a reader after the Markdown should find a link to it.
            $byMajor[$entry['typo3-major']][] = [
                'permalink' => $permalink,
                'path' => $entry['path'],
                ...$this->pageFiles->of($entry['path']),
                ...$entry,
                ...$this->classesOf($classesByPath[$entry['path']] ?? [], $entry['anchor']),
            ];
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

    /**
     * The places of the class index, by the page they stand on.
     *
     * The index itself lists them by class; an entry wants them the other
     * way round, and gets them from there so both files say the same.
     *
     * @return array<string, array<string, list<Place>>>
     */
    private function classesByPath(RenderCommand $renderCommand): array
    {
        $byPath = [];
        foreach ($this->classIndex->of($renderCommand->getDocumentArray()) as $name => $class) {
            foreach ($class['places'] as $place) {
                $byPath[$place['path']][$name][] = $place;
            }
        }

        return $byPath;
    }

    /**
     * The classes an entry speaks of, each with the places in it.
     *
     * The entry already names its page and release. A place keeps its anchor
     * only where it lies below a label of its own inside the entry, and
     * otherwise just the section it stands in.
     *
     * @param array<string, list<Place>> $classes
     * @return array{classes?: array<string, list<array<string, mixed>>>}
     */
    private function classesOf(array $classes, string $entryAnchor): array
    {
        if ($classes === []) {
            return [];
        }

        foreach ($classes as $name => $places) {
            foreach ($places as $i => $place) {
                unset($place['path'], $place['typo3-version']);
                if (($place['anchor'] ?? '') === $entryAnchor) {
                    unset($place['anchor']);
                }

                $places[$i] = $place;
            }

            $classes[$name] = $places;
        }

        return ['classes' => $classes];
    }
}
