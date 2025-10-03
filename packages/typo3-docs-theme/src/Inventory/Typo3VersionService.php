<?php

namespace T3Docs\Typo3DocsTheme\Inventory;

use T3Docs\Typo3DocsTheme\Settings\Typo3DocsThemeSettings;
use T3Docs\VersionHandling\Typo3VersionMapping;

class Typo3VersionService
{
    /**
     * @see https://regex101.com/r/Kx7VyS/2
     */
    private const VERSION_MINOR_REGEX = '/^(\d+\.\d+)\.\d+$/';
    /**
     * @see https://regex101.com/r/Ljhv1I/1
     */
    private const VERSION_MAJOR_REGEX = '/^(\d+)(\.\d+)?(\.\d+)?$/';
    public function __construct(
        private readonly Typo3DocsThemeSettings $settings,
    ) {}

    public function getPreferredVersion(): string
    {
        if ($this->settings->hasSettings('typo3_core_preferred')) {
            $preferred = $this->settings->getSettings('typo3_core_preferred');
            return $this->resolveVersion($preferred);
        }
        return Typo3VersionMapping::getDefault()->getVersion();
    }

    public function resolveCoreVersion(string $versionName): string
    {
        $version = ltrim($versionName, 'v');
        if (preg_match(self::VERSION_MAJOR_REGEX, $version, $matches)) {
            $version = $matches[1];
        }
        $version = Typo3VersionMapping::tryFrom($version)?->getVersion() ?? $version;

        return $this->resolveVersion($version);
    }

    public function resolveVersion(string $versionName): string
    {
        $version = trim($versionName, 'v');
        if (preg_match(self::VERSION_MINOR_REGEX, $version, $matches)) {
            return $matches[1];
        }
        $mappedVersion = Typo3VersionMapping::tryFrom($version);
        if ($mappedVersion !== null) {
            return $mappedVersion->getVersion();
        }
        if ($version === '') {
            return Typo3VersionMapping::tryFrom('stable')->getVersion();
        }
        return $version;
    }
}
