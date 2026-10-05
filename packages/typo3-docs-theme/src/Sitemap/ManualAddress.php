<?php

declare(strict_types=1);

namespace T3Docs\Typo3DocsTheme\Sitemap;

use T3Docs\Typo3DocsTheme\Inventory\Typo3InventoryRepository;
use T3Docs\Typo3DocsTheme\Permalinks\Permalinks;

use function in_array;

/**
 * Where on docs.typo3.org the manual being rendered is published.
 *
 * A render does not know where it will be deployed, and "project-home" in
 * guides.xml is where the manual's home is, which is not always the same: a
 * permalink in the Changelog, Packagist for Fluid, and "main" on the 13.4
 * branch of the ViewHelper Reference. The interlinks know: every other
 * manual reaches this one by its shortcode and version, at the address the
 * official manuals are published under. So that is the address, for the
 * official manuals and the system extensions, which follow that layout.
 * Other manuals have none here.
 */
final class ManualAddress
{
    /** The kinds of interlink that name a manual published by TYPO3. */
    private const OFFICIAL = ['default', 'core'];

    public function __construct(
        private readonly Typo3InventoryRepository $inventoryRepository,
        private readonly Permalinks $permalinks,
    ) {}

    public function of(?string $projectVersion): ?string
    {
        $shortcode = $this->permalinks->shortcode();
        if ($shortcode === '') {
            return null;
        }

        $parts = $this->inventoryRepository->parseOnly($shortcode);
        if ($parts === null || !in_array($parts->kind, self::OFFICIAL, true)) {
            return null;
        }

        $version = $this->permalinks->normalizeVersion($projectVersion);

        return $this->inventoryRepository->previewUrl($version === '' ? $shortcode : $shortcode . '/' . $version);
    }
}
