<?php

namespace T3Docs\Typo3DocsTheme\Inventory;

enum Typo3VersionMapping: string
{
    case Dev = 'dev';
    case Stable = 'stable';
    case OldStable = 'oldstable';

    public function getVersion(): string
    {
        return match ($this) {
            Typo3VersionMapping::Dev => 'main',
            Typo3VersionMapping::Stable => '12.4',
            Typo3VersionMapping::OldStable => '11.5',
        };
    }

    public static function getDefault(): Typo3VersionMapping
    {
        return Typo3VersionMapping::Stable;
    }
}
