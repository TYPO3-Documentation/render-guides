<?php

namespace T3Docs\Typo3DocsTheme\Inventory;

enum Typo3VersionMapping: string
{
    case Dev = 'dev';
    case Stable = 'stable';
    case OldStable = 'oldstable';
    case V13 = '13';
    case V12 = '12';
    case V11 = '11';
    case V10 = '10';
    case V9 = '9';
    case V8 = '8';
    case V7 = '7';
    case V6 = '6';

    public function getVersion(): string
    {
        return match ($this) {
            Typo3VersionMapping::Dev => 'main',
            Typo3VersionMapping::Stable => '12.4',
            Typo3VersionMapping::OldStable => '11.5',
            Typo3VersionMapping::V13 => 'main',
            Typo3VersionMapping::V12 => '12.4',
            Typo3VersionMapping::V11 => '11.5',
            Typo3VersionMapping::V10 => '10.4',
            Typo3VersionMapping::V9 => '9.5',
            Typo3VersionMapping::V8 => '8.7',
            Typo3VersionMapping::V7 => '7.6',
            Typo3VersionMapping::V6 => '6.2',
        };
    }

    public static function getDefault(): Typo3VersionMapping
    {
        return Typo3VersionMapping::Stable;
    }
}
