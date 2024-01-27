<?php

namespace T3Docs\Typo3DocsTheme\Settings;

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
}
