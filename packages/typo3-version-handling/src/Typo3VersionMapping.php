<?php

namespace T3Docs\VersionHandling;

enum Typo3VersionMapping: string
{
    case Dev = 'dev';
    case Stable = 'stable';
    case OldStable = 'oldstable';
    case V14 = '14';
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
            // @TODO - When changing these versions, please
            //         also do a PR on https://github.com/TYPO3GmbH/site-intercept/blob/develop/legacy_hook/composer.json
            //         to raise the required composer package version!
            Typo3VersionMapping::Dev => 'main',
            Typo3VersionMapping::Stable => '13.4',
            Typo3VersionMapping::OldStable => '12.4',
            Typo3VersionMapping::V14 => 'main',
            Typo3VersionMapping::V13 => '13.4',
            Typo3VersionMapping::V12 => '12.4',
            Typo3VersionMapping::V11 => '11.5',
            Typo3VersionMapping::V10 => '10.4',
            Typo3VersionMapping::V9 => '9.5',
            Typo3VersionMapping::V8 => '8.7',
            Typo3VersionMapping::V7 => '7.6',
            Typo3VersionMapping::V6 => '6.2',
        };
    }

    /**
     * @return Typo3VersionMapping[]
     */
    public static function getLtsVersions(): array
    {
        return [
            Typo3VersionMapping::V13,
            Typo3VersionMapping::V12,
        ];
    }

    public static function getDefault(): Typo3VersionMapping
    {
        return Typo3VersionMapping::Stable;
    }

    /**
     * @return list<string>
     */
    public static function getAllVersions(): array
    {
        return array_map(static fn(Typo3VersionMapping $enumValue): string => $enumValue->getVersion(), self::cases());
    }

    public static function getMajorVersionOfMain(): Typo3VersionMapping
    {
        return Typo3VersionMapping::V14;
    }
}
