<?php

namespace T3Docs\Typo3DocsTheme\Inventory;

use phpDocumentor\Guides\ReferenceResolvers\AnchorNormalizer;
use T3Docs\VersionHandling\DefaultInventories;

final class DefaultInterlinkParser implements InterlinkParserInterface
{
    /**
     * @see https://regex101.com/r/OwYQxf/1
     *
     * https://getcomposer.org/doc/04-schema.md#name
     */
    private const EXTENSION_INTERLINK_REGEX = '/^([^\/\s]+)\/([^\/\@\s]+)([\/\@]([^\/\s]+))?$/';
    public function __construct(
        private readonly AnchorNormalizer $anchorNormalizer
    ) {}

    public function parse(string $key): ?InterlinkParts
    {
        $normalizedKey = $this->anchorNormalizer->reduceAnchor($key);

        // legacy: ext_* → typo3/cms-<dash-name>
        if (\str_starts_with($key, 'ext_')) {
            $pkg = 'cms-' . \str_replace('_', '-', \substr($key, 4));
            return new InterlinkParts($normalizedKey, 'legacy-ext', 'typo3', $pkg, null);
        }

        // Default inventories
        if ($default = DefaultInventories::tryFrom($key)) {
            // This catches cases like t3coreapi
            return new InterlinkParts($normalizedKey, 'default', 'typo3', $key, null);
        }

        // vendor/package[/version]
        if (\preg_match(self::EXTENSION_INTERLINK_REGEX, $key, $m)) {
            [$full, $vendor, $package] = $m;
            $version = $m[4] ?? null;

            if (DefaultInventories::tryFrom($vendor)) {
                // This catches cases like t3coreapi/12.4
                return new InterlinkParts($normalizedKey, 'default', 'typo3', $vendor, $package);
            }
            return new InterlinkParts(
                $normalizedKey,
                $vendor === 'typo3' ? 'core' : 'package',
                $vendor,
                $package,
                $version
            );
        }

        // fallback: replace first non-alnum with '/'
        $fallback = \preg_replace('/[^a-zA-Z0-9]/', '/', $key, 1);
        if (\is_string($fallback) && \preg_match(self::EXTENSION_INTERLINK_REGEX, $fallback, $m)) {
            [$full, $vendor, $package] = $m;
            $version = $m[4] ?? null;

            if (DefaultInventories::tryFrom($vendor)) {
                // This catches cases like t3coreapi@12.4
                return new InterlinkParts($normalizedKey, 'default', 'typo3', $vendor, $package);
            }
            return new InterlinkParts(
                $normalizedKey,
                $vendor === 'typo3' ? 'core' : 'package',
                $vendor,
                $package,
                $version
            );
        }
        return null;
    }
}
