<?php

namespace T3Docs\Typo3DocsTheme\Twig;

final class ContextRegistry
{
    /**
     * @var array<string, string>
     */
    private static array $store = [];

    public static function set(string $key, string $value): void
    {
        self::$store[$key] = $value;
    }

    public static function get(string $key): ?string
    {
        return self::$store[$key] ?? null;
    }
}
