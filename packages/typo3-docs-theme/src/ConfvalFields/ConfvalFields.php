<?php

declare(strict_types=1);

namespace T3Docs\Typo3DocsTheme\ConfvalFields;

use T3Docs\Typo3DocsTheme\Settings\Typo3DocsThemeSettings;

use function array_filter;
use function array_map;
use function array_merge;
use function array_values;
use function explode;
use function in_array;
use function preg_replace;
use function sprintf;
use function strtolower;
use function trim;

/**
 * The fields a manual allows on ".. confval::", from its "confval-fields"
 * setting.
 *
 * A field is matched by its exact name, so ":Default:", ":default:" and
 * ":de-fault:" are three different fields, and all but one fall through
 * without a word: shown on the page as a field of their own, indexed in
 * confvals.json under that name, and empty as a column of a confval-menu. A
 * manual that names its fields has every other one reported.
 *
 * Unset, which is the default, nothing is checked.
 */
final class ConfvalFields
{
    /** The fields the confval directive reads itself. */
    public const BUILT_IN = ['name', 'type', 'default', 'required', 'noindex', 'parent'];

    /** The fields the theme reads from any confval, for the search. */
    public const THEME = ['searchFacet', 'searchKeywords'];

    /** @var list<string>|null */
    private ?array $declared = null;

    public function __construct(
        private readonly Typo3DocsThemeSettings $themeSettings,
    ) {}

    public function isChecked(): bool
    {
        return $this->declared() !== [];
    }

    /** Whether a field needs no declaration, or is declared. */
    public function isAllowed(string $field): bool
    {
        return in_array($field, self::BUILT_IN, true)
            || in_array($field, self::THEME, true)
            || in_array($field, $this->declared(), true);
    }

    /**
     * What to tell an author who wrote a field that is not allowed: the
     * spelling meant, when the field differs from an allowed one only in
     * case, spaces, hyphens or underscores.
     */
    public function advice(string $field): string
    {
        foreach (self::BUILT_IN as $builtIn) {
            if ($this->normalize($builtIn) === $this->normalize($field)) {
                return sprintf('Did you mean the built-in "%s"?', $builtIn);
            }
        }
        foreach (array_merge(self::THEME, $this->declared()) as $allowed) {
            if ($this->normalize($allowed) === $this->normalize($field)) {
                return sprintf('Did you mean "%s"?', $allowed);
            }
        }

        return 'Correct it, or declare it in confval-fields in guides.xml.';
    }

    /** @return list<string> */
    private function declared(): array
    {
        return $this->declared ??= array_values(array_filter(
            array_map(trim(...), explode(',', $this->themeSettings->getSettings('confval_fields'))),
            static fn(string $field): bool => $field !== '',
        ));
    }

    private function normalize(string $field): string
    {
        return strtolower((string) preg_replace('/[\s_-]+/', '', $field));
    }
}
