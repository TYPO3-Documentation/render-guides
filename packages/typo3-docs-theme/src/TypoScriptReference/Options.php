<?php

declare(strict_types=1);

namespace T3Docs\Typo3DocsTheme\TypoScriptReference;

use phpDocumentor\Guides\ReferenceResolvers\AnchorNormalizer;

use function count;
use function preg_replace;

/**
 * Options by their anchor and by the paths they declare, found by the full
 * path a role writes. @see OptionPaths
 *
 * The anchor is tried first, "stdWrap.parseFunc" as "confval-stdwrap-parsefunc".
 * A declared path counts only where one option declares it.
 *
 * An Extbase plugin is configured below a key of its own, which the reference
 * documents once, as "plugin": "plugin.tx_blog.persistence.storagePid" is
 * found as "plugin.persistence.storagePid".
 *
 * @phpstan-import-type Option from ExternalTypoScript
 */
final class Options
{
    /** The key of a plugin, "plugin.tx_blog." or "plugin.tx_blog_list.". */
    private const PLUGIN_KEY = '/^plugin\.tx_[A-Za-z0-9_]+\./';

    /** @var array<string, Option> */
    private array $byAnchor = [];

    /** @var array<string, true> the anchors of the TypoScript and TSconfig options, which a role finds by itself */
    private array $typoScript = [];

    /** @var array<string, list<string>> the anchors of the options that declare each path */
    private array $anchorsByPath = [];

    /**
     * @param Option $option
     * @param list<string> $paths the paths the option declares
     * @param bool $typoScript whether a role finds it by itself, or only by its name
     */
    public function add(string $anchor, array $option, array $paths, bool $typoScript = true): void
    {
        if (isset($this->byAnchor[$anchor])) {
            return;
        }
        $this->byAnchor[$anchor] = $option;
        if (!$typoScript) {
            return;
        }
        $this->typoScript[$anchor] = true;
        foreach ($paths as $path) {
            $this->anchorsByPath[$path][] = $anchor;
        }
    }

    /**
     * Any option by the name of its confval, as ":confval:" names it: what a
     * role names in angle brackets, "module.tx_extbase <some-key>", whatever
     * kind of option it is.
     *
     * @return Option|null
     */
    public function named(string $name, AnchorNormalizer $anchorNormalizer): ?array
    {
        return $this->byAnchor['confval-' . $anchorNormalizer->reduceAnchor($name)] ?? null;
    }

    /** @return Option|null */
    public function find(string $path, AnchorNormalizer $anchorNormalizer): ?array
    {
        $option = $this->findPath($path, $anchorNormalizer);
        if ($option !== null) {
            return $option;
        }

        $pluginPath = preg_replace(self::PLUGIN_KEY, 'plugin.', $path) ?? $path;

        return $pluginPath !== $path ? $this->findPath($pluginPath, $anchorNormalizer) : null;
    }

    /** @return Option|null */
    private function findPath(string $path, AnchorNormalizer $anchorNormalizer): ?array
    {
        $anchor = 'confval-' . $anchorNormalizer->reduceAnchor($path);
        if (isset($this->typoScript[$anchor])) {
            return $this->byAnchor[$anchor];
        }

        $anchors = $this->anchorsByPath[$path] ?? [];

        return count($anchors) === 1 ? $this->byAnchor[$anchors[0]] : null;
    }

    /**
     * An object type or a function documented as an option of its own, by the
     * word a role writes: "USER", "COA_INT", "stdWrap".
     *
     * The TypoScript reference gives such an option the anchor
     * "confval-cobj-<word>" for a content object, and "confval-<word>" for
     * anything else, or "confval-function-<word>" for a function whose anchor
     * a published property already had: "HTMLparser_tags", as the property
     * "tags" of "HTMLparser" was "htmlparser-tags". It names the option with
     * the word as written. Anchor and name are needed both:
     * "stdWrap" alone names sixteen options, and ":typoscript:`page`", the
     * object of "page = PAGE", is not the type "PAGE" its anchor would find.
     *
     * @return Option|null
     */
    public function findWord(string $word, AnchorNormalizer $anchorNormalizer): ?array
    {
        $slug = $anchorNormalizer->reduceAnchor($word);
        foreach (['confval-cobj-' . $slug, 'confval-' . $slug, 'confval-function-' . $slug] as $anchor) {
            $option = isset($this->typoScript[$anchor]) ? $this->byAnchor[$anchor] : null;
            if ($option !== null && $option['name'] === $word) {
                return $option;
            }
        }

        return null;
    }
}
