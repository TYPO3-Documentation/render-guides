<?php

namespace T3Docs\Typo3DocsTheme\Twig;

final class ContextRegistry
{
    private static array $store = [];

    public static function set(string $key, mixed $value): void
    {
        self::$store[$key] = $value;
    }

    public static function get(string $key): mixed
    {
        return self::$store[$key] ?? null;
    }
}
