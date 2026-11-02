<?php

declare(strict_types=1);

namespace T3Docs\Typo3Api\Documentation\Index;

use Psr\Log\LoggerInterface;

/**
 * Where the TYPO3 documentation speaks of each class, read from the indexes
 * that the manuals publish.
 *
 * Every manual publishes "classes.json" beside its pages: the classes it
 * names, and for each the page and anchor of every place that names it. This
 * turns those around, so that the page of a class can list the places. Which
 * manuals there are is in "manuals.json" of the docs homepage, the manuals of
 * its main menu with the address of each version. The titles of the places
 * come from the "objects.inv.json" of the manual, as "classes.json" holds
 * only anchors.
 *
 * Each version of the API lists the same version of a manual. A manual that
 * has only "main" has no versions, such as the Changelog, and is listed for
 * every version of the API. The Changelog lists only the entries up to the
 * version of the API, so 13.4 does not list what changed in 14.0.
 *
 * Reading is done once, when the API is built, and not by the pages: the
 * docs server sends no CORS headers, and the index of the Changelog is more
 * than 2 MB.
 *
 * @phpstan-type Place array{title: string, url: string, kind: string}
 * @phpstan-type Group array{version: string|null, places: list<Place>}
 * @phpstan-type Manual array{name: string, count: int, groups: list<Group>}
 */
final class DocumentationIndex
{
    /** The kinds of "classes.json", most telling first. */
    private const KINDS = ['definition', 'inline', 'code'];

    public function __construct(private readonly LoggerInterface $logger) {}

    /**
     * @param string $manualsLocation the URL or path of "manuals.json"
     * @param string $typo3Version the version of the API, such as "main" or "14.3"
     * @param list<string> $excluded the shortcodes of the manuals not to list, such as "h2document"
     *
     * @return array<string, list<Manual>> indexed by the class, such as "\TYPO3\CMS\Core\Site\SiteFinder"
     */
    public function load(string $manualsLocation, string $typo3Version, array $excluded = []): array
    {
        $manuals = $this->read($manualsLocation);
        if (!is_array($manuals['manuals'] ?? null)) {
            $this->logger->warning(sprintf(
                'The manuals could not be read from "%s". The pages show no documentation.',
                $manualsLocation,
            ));

            return [];
        }

        $index = [];
        foreach ($manuals['manuals'] as $shortcode => $manual) {
            if (in_array((string)$shortcode, $excluded, true)) {
                continue;
            }

            $base = $this->baseOf($manual['versions'] ?? [], $typo3Version);
            if ($base === null) {
                continue;
            }

            $base = $this->resolve($base, $manualsLocation);

            // Not every manual has one: a manual not rendered again since the
            // file was added has none, and that is no reason to fail.
            $classes = $this->read($base . 'classes.json');
            if (!is_array($classes['classes'] ?? null)) {
                continue;
            }

            $inventory = $this->read($base . 'objects.inv.json') ?? [];
            $permalink = (string)($classes['project']['permalink'] ?? '');
            // The title the manual gives itself: the menu names a system
            // extension by its key, such as "webhooks".
            $name = (string)($classes['project']['title'] ?? '') ?: (string)($manual['name'] ?? '');

            foreach ($classes['classes'] as $class => $entry) {
                $places = $this->placesOf($entry['places'] ?? [], $typo3Version, $inventory, $permalink, $base);
                if ($places === []) {
                    continue;
                }

                $index[(string)$class][] = [
                    'name' => $name,
                    'count' => count($places),
                    'groups' => $this->group($places),
                ];
            }
        }

        // The manuals in the order of the menu, and the Changelog after them:
        // what is documented now comes before the history of how it got so.
        foreach ($index as &$manualsOfClass) {
            usort($manualsOfClass, static fn(array $a, array $b): int => ($a['groups'][0]['version'] !== null) <=> ($b['groups'][0]['version'] !== null));
        }

        return $index;
    }

    /**
     * One place for each title of a page: a section that documents a class
     * and also shows it in an example is listed once, as the more telling
     * kind. So is a page about a class that mentions it at the top and
     * documents it further down, as both are titled by the page.
     *
     * @param list<array<string, mixed>> $places
     * @param array<string, mixed> $inventory
     *
     * @return list<Place&array{version: string|null}>
     */
    private function placesOf(array $places, string $typo3Version, array $inventory, string $permalink, string $base): array
    {
        $byTitle = [];
        foreach ($places as $place) {
            $anchor = (string)($place['anchor'] ?? '');
            $path = (string)($place['path'] ?? '');
            $kind = (string)($place['kind'] ?? '');
            $version = isset($place['typo3-version']) ? (string)$place['typo3-version'] : null;
            if ($anchor === '' || !in_array($kind, self::KINDS, true)) {
                continue;
            }

            if ($version !== null && $typo3Version !== 'main' && version_compare(self::release($version), $typo3Version, '>')) {
                continue;
            }

            $title = $this->titleOf($anchor, $path, $inventory);
            $key = $path . "\n" . $title;
            if (isset($byTitle[$key])
                && array_search($kind, self::KINDS, true) >= array_search($byTitle[$key]['kind'], self::KINDS, true)
            ) {
                continue;
            }

            // The stronger kind also brings its anchor: the definition of
            // a class is a more exact target than the top of its page.
            $byTitle[$key] = [
                'title' => $title,
                'url' => str_contains($permalink, '{anchor}')
                    ? str_replace('{anchor}', $anchor, $permalink)
                    : $base . $path . '.html#' . $anchor,
                'kind' => $kind,
                'version' => $version,
            ];
        }

        return array_values($byTitle);
    }

    /**
     * The title of the section, or of the page where the section is the
     * class itself: the anchor of the section that documents a class is titled
     * by the name of the class, which says nothing on its own page.
     *
     * @param array<string, mixed> $inventory
     */
    private function titleOf(string $anchor, string $path, array $inventory): string
    {
        $label = $inventory['std:label'][$anchor][3] ?? null;
        if (is_string($label) && $label !== '' && !str_starts_with($label, '\\')) {
            return $label;
        }

        $page = $inventory['std:doc'][$path][3] ?? null;
        if (is_string($page) && $page !== '') {
            return $page;
        }

        return is_string($label) && $label !== '' ? $label : $anchor;
    }

    /**
     * The places of a Changelog by release, newest first, and the places of
     * any other manual as one group, the sections that document the class
     * first and the examples last.
     *
     * @param list<Place&array{version: string|null}> $places
     *
     * @return list<Group>
     */
    private function group(array $places): array
    {
        $groups = [];
        foreach ($places as $place) {
            $version = $place['version'];
            unset($place['version']);
            $groups[$version ?? ''][] = $place;
        }

        uksort($groups, static fn(string|int $a, string|int $b): int => version_compare(self::release((string)$b), self::release((string)$a)));

        $result = [];
        foreach ($groups as $version => $group) {
            usort($group, static fn(array $a, array $b): int => array_search($a['kind'], self::KINDS, true) <=> array_search($b['kind'], self::KINDS, true)
                ?: ($version === '' ? 0 : strnatcasecmp($a['title'], $b['title'])));
            $result[] = ['version' => $version === '' ? null : (string)$version, 'places' => $group];
        }

        return $result;
    }

    /** "12.4.x" holds the changes of the patch releases of 12.4. */
    private static function release(string $version): string
    {
        return preg_replace('/\.x$/', '', $version) ?? $version;
    }

    /** @param list<array<string, mixed>> $versions */
    private function baseOf(array $versions, string $typo3Version): string|null
    {
        foreach ($versions as $version) {
            if (($version['version'] ?? null) === $typo3Version) {
                return (string)($version['base'] ?? '');
            }
        }

        if (count($versions) === 1 && ($versions[0]['version'] ?? null) === 'main') {
            return (string)($versions[0]['base'] ?? '');
        }

        return null;
    }

    /** A base in "manuals.json" is a URL; a path is relative to that file. */
    private function resolve(string $base, string $manualsLocation): string
    {
        $base = rtrim($base, '/') . '/';
        if (preg_match('#^[a-z][a-z0-9+.-]*://#i', $base) === 1 || str_starts_with($base, '/')) {
            return $base;
        }

        return dirname($manualsLocation) . '/' . $base;
    }

    /** @return array<string, mixed>|null */
    private function read(string $location): array|null
    {
        $context = stream_context_create(['http' => ['timeout' => 60, 'user_agent' => 'api.typo3.org']]);
        $content = @file_get_contents($location, false, $context);
        if ($content === false) {
            return null;
        }

        $data = json_decode($content, true);

        return is_array($data) ? $data : null;
    }
}
