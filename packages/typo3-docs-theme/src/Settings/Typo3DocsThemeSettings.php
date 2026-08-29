<?php

namespace T3Docs\Typo3DocsTheme\Settings;

use function in_array;
use function strtolower;

final class Typo3DocsThemeSettings
{
    /**
     * @param array<string, string> $settings
     */
    public function __construct(
        private readonly array $settings
    ) {}

    public function hasSettings(string $key): bool
    {
        return isset($this->settings[$key]);
    }

    public function getSettings(string $key, string $default = ''): string
    {
        if (!$this->hasSettings($key)) {
            return $default;
        }
        return $this->settings[$key];
    }

    /**
     * @return array<string, string>
     */
    public function getAllSettings(): array
    {
        return $this->settings;
    }

    /**
     * Interpret a string setting as a boolean flag. Accepts the common truthy
     * tokens and treats everything else (including an unset key) as false, so
     * an unrecognised value fails safe to "off".
     */
    public function isEnabled(string $key): bool
    {
        return in_array(strtolower($this->getSettings($key, 'false')), ['1', 'true', 'yes', 'on'], true);
    }
}
