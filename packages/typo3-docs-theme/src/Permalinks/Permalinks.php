<?php

declare(strict_types=1);

namespace T3Docs\Typo3DocsTheme\Permalinks;

use T3Docs\Typo3DocsTheme\Settings\Typo3DocsThemeSettings;
use T3Docs\VersionHandling\DefaultInventories;

use function explode;
use function preg_match;
use function trim;

/**
 * How a docs.typo3.org permalink is spelled.
 *
 * A permalink names a place in the documentation rather than a file, so it
 * survives a page being moved or renamed. It is the only link a rendered
 * artifact can carry that still works once the artifact has been copied
 * somewhere else, which is why the Markdown front matter, the Changelog index
 * and the table of contents all write one.
 *
 * The rule is the same for all of them and lives here once: the manual's
 * interlink shortcode, the anchor, and the version -- unless the manual is one
 * of the unversioned ones, which are only ever deployed as "main".
 */
final class Permalinks
{
    private const BASE = 'https://docs.typo3.org/permalink/';

    /**
     * Where the anchor goes in a permalink pattern, for consumers that hold the
     * pattern once and fill it in per entry.
     */
    public const ANCHOR_PLACEHOLDER = '{anchor}';

    public function __construct(
        private readonly Typo3DocsThemeSettings $themeSettings,
    ) {}

    /** The manual's interlink shortcode, or "" when it declares none. */
    public function shortcode(): string
    {
        return $this->themeSettings->getSettings('interlink_shortcode');
    }

    /**
     * The permalink of one anchor, or "" when it cannot be spelled.
     *
     * A manual without a shortcode has no permalinks, and an anchor is needed
     * to point at anything.
     */
    public function forAnchor(string $anchor, string|null $projectVersion): string
    {
        $shortcode = $this->shortcode();
        if ($shortcode === '' || $anchor === '') {
            return '';
        }

        return self::BASE . $shortcode . ':' . $anchor . $this->versionSuffix($projectVersion);
    }

    /**
     * The permalink of every anchor at once: the same string with
     * ANCHOR_PLACEHOLDER where the anchor goes, or "" when the manual has no
     * permalinks.
     */
    public function pattern(string|null $projectVersion): string
    {
        return $this->forAnchor(self::ANCHOR_PLACEHOLDER, $projectVersion);
    }

    /**
     * The "@version" a permalink has to carry, or "" when it must not carry
     * one.
     *
     * A permalink without a version resolves to the latest stable release. That
     * is right for a manual deployed only as "main" and wrong for everything
     * else: a link written in the 13.4 manual would otherwise send its reader
     * into whatever manual is current later.
     */
    public function versionSuffix(string|null $projectVersion): string
    {
        $shortcode = $this->shortcode();
        if ($shortcode === '') {
            return '';
        }

        $inventory = DefaultInventories::tryFrom($shortcode);
        if ($inventory !== null && !$inventory->isVersioned()) {
            return '';
        }

        $version = $this->normalizeVersion($projectVersion);

        return $version === '' ? '' : '@' . $version;
    }

    /**
     * The project version in the form a URL and a metadata field can carry, or
     * "" when there is none and when there is one that cannot be carried.
     *
     * A checkout names itself "main (development)"; both want the bare "main".
     * A project that names no version at all is left alone -- the theme treats
     * the version as optional everywhere else too.
     */
    public function normalizeVersion(string|null $projectVersion): string
    {
        $version = explode(' ', trim((string) $projectVersion))[0];
        if ($version === '' || preg_match('/^[A-Za-z0-9][A-Za-z0-9._-]*$/', $version) !== 1) {
            return '';
        }

        return $version;
    }
}
