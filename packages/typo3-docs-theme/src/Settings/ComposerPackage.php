<?php

declare(strict_types=1);

namespace T3Docs\Typo3DocsTheme\Settings;

use function dirname;
use function file_get_contents;
use function in_array;
use function is_array;
use function is_file;
use function is_string;
use function json_decode;
use function preg_match;

/**
 * The package a manual documents, from the composer.json in the rendered
 * directory or the one above it -- where it sits beside "Documentation/".
 */
final class ComposerPackage
{
    /** @see https://getcomposer.org/doc/04-schema.md#name */
    private const NAME = '/^[a-z0-9]([_.-]?[a-z0-9]+)*\/[a-z0-9](([_.]|-{1,2})?[a-z0-9]+)*$/';

    private const THIRD_PARTY_EXTENSION = 'typo3-cms-extension';
    private const SYSTEM_EXTENSION = 'typo3-cms-framework';

    private function __construct(
        public readonly string $name,
        public readonly string $type,
        public readonly string $extensionKey = '',
        public readonly string $source = '',
    ) {}

    public static function findFor(string $input): ?self
    {
        foreach ([$input, dirname($input)] as $directory) {
            $file = $directory . '/composer.json';
            if (!is_file($file)) {
                continue;
            }

            $composer = json_decode((string) file_get_contents($file), true);
            $name = is_array($composer) ? ($composer['name'] ?? null) : null;
            if (!is_string($name) || preg_match(self::NAME, $name) !== 1) {
                return null;
            }

            $type = $composer['type'] ?? null;
            $typo3 = is_array($composer['extra'] ?? null) ? ($composer['extra']['typo3/cms'] ?? null) : null;
            $extensionKey = is_array($typo3) ? ($typo3['extension-key'] ?? null) : null;
            $support = $composer['support'] ?? null;
            $source = is_array($support) ? ($support['source'] ?? null) : null;

            return new self(
                $name,
                is_string($type) ? $type : '',
                is_string($extensionKey) ? $extensionKey : '',
                is_string($source) ? $source : '',
            );
        }

        return null;
    }

    /** Published in "p/" or "c/" on docs.typo3.org */
    public function isExtension(): bool
    {
        return in_array($this->type, [self::THIRD_PARTY_EXTENSION, self::SYSTEM_EXTENSION], true);
    }

    public function isThirdPartyExtension(): bool
    {
        return $this->type === self::THIRD_PARTY_EXTENSION;
    }
}
