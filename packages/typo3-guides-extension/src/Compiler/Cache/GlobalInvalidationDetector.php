<?php

declare(strict_types=1);

namespace T3Docs\GuidesExtension\Compiler\Cache;

/**
 * Detects changes that require a full rebuild of all documents.
 *
 * Certain changes affect all pages:
 * - Toctree structure changes (navigation)
 * - Theme or template changes
 * - Global configuration changes
 * - Interlink inventory changes
 */
final class GlobalInvalidationDetector
{
    /**
     * Files/patterns that trigger full rebuild when changed.
     */
    private const GLOBAL_PATTERNS = [
        // Configuration files
        'guides.xml',
        'Settings.cfg',
        'conf.py',
        // Theme files
        '_static/',
        '_templates/',
        // Interlink files
        'objects.inv',
    ];

    /**
     * Check if any changes require a full rebuild.
     *
     * @param ChangeDetectionResult $changes Detected changes
     * @param string|null $settingsHash Current settings hash
     * @param string|null $cachedSettingsHash Previous settings hash
     * @return bool True if full rebuild is required
     */
    public function requiresFullRebuild(
        ChangeDetectionResult $changes,
        ?string $settingsHash = null,
        ?string $cachedSettingsHash = null,
    ): bool {
        // Check if settings changed
        if ($settingsHash !== null && $cachedSettingsHash !== null) {
            if ($settingsHash !== $cachedSettingsHash) {
                return true;
            }
        }

        // Check if any global files changed
        $allChangedFiles = array_merge($changes->dirty, $changes->new, $changes->deleted);

        foreach ($allChangedFiles as $file) {
            if ($this->isGlobalFile($file)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if a file is a global file that affects all documents.
     */
    private function isGlobalFile(string $filePath): bool
    {
        $normalizedPath = str_replace('\\', '/', $filePath);

        foreach (self::GLOBAL_PATTERNS as $pattern) {
            if (str_ends_with($pattern, '/')) {
                // Directory pattern
                if (str_contains($normalizedPath, $pattern)) {
                    return true;
                }
            } else {
                // File pattern
                if (str_ends_with($normalizedPath, '/' . $pattern) || $normalizedPath === $pattern) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Check if toctree structure changed.
     * This is detected by comparing the document hierarchy.
     *
     * @param array<string, string[]> $oldToctree Previous toctree structure
     * @param array<string, string[]> $newToctree Current toctree structure
     * @return bool True if structure changed
     */
    public function hasToctreeChanged(array $oldToctree, array $newToctree): bool
    {
        // Simple comparison - if keys or values differ, structure changed
        if (count($oldToctree) !== count($newToctree)) {
            return true;
        }

        foreach ($oldToctree as $parent => $children) {
            if (!isset($newToctree[$parent])) {
                return true;
            }

            $oldChildren = $children;
            $newChildren = $newToctree[$parent];
            sort($oldChildren);
            sort($newChildren);

            if ($oldChildren !== $newChildren) {
                return true;
            }
        }

        return false;
    }
}
