<?php

namespace T3Docs\VersionHandling;

enum DefaultInventories: string
{
    // IMPORTANT: If new default inventories are added, please
    //            also add them to `Documentation/Developer/InterlinkInventories.rst`.
    case t3docs = 't3docs';
    case changelog = 'changelog';
    case t3ts45 = 't3ts45';
    case t3coreapi = 't3coreapi';
    case t3tca = 't3tca';
    case t3tsconfig = 't3tsconfig';
    case t3tsref = 't3tsref';
    case t3viewhelper = 't3viewhelper';
    case t3editors = 't3editors';
    case t3sitepackage = 't3sitepackage';
    case t3start = 't3start';
    case t3translate = 't3translate';
    case h2document = 'h2document';
    case t3content = 't3content';
    case t3writing = 't3writing';
    case t3org = 't3org';
    case t3contribute = 't3contribute';
    case fluid = 'fluid';
    case t3renderguides = 't3renderguides';
    case t3exceptions = 't3exceptions';
    case api = 'api';
    case policy = 'policy';
    case guide_policy = 'guide-policy';

    public function isVersioned(): bool
    {

        return match ($this) {
            // Main doc page, it is only deployed to main
            DefaultInventories::t3docs => false,

            // Changelog, it is only deployed to main
            DefaultInventories::changelog => false,

            // Team Guides, they are commonly not versioned
            DefaultInventories::h2document => false,
            DefaultInventories::t3content => false,
            DefaultInventories::t3contribute => false,
            DefaultInventories::t3writing => false,
            DefaultInventories::t3org => false,
            DefaultInventories::policy => false,
            DefaultInventories::guide_policy => false,

            // Other
            DefaultInventories::fluid => false,
            DefaultInventories::t3renderguides => false,
            DefaultInventories::t3exceptions => false,

            default => true,
        };
    }

    public function getUrl(string $version): string
    {
        if ($version === 'main') {
            switch ($this) {
                case DefaultInventories::t3tsconfig:
                    return DefaultInventories::t3tsref->getUrl($version);
                case DefaultInventories::t3ts45:
                    return DefaultInventories::t3tsref->getUrl($version);
            }
        }
        if ($version === '13.4') {
            switch ($this) {
                case DefaultInventories::t3tsconfig:
                    return DefaultInventories::t3tsref->getUrl($version);
                case DefaultInventories::t3ts45:
                    return DefaultInventories::t3tsref->getUrl($version);
            }
        }
        if ($version === '12.4') {
            switch ($this) {
                case DefaultInventories::t3tsconfig:
                    return DefaultInventories::t3tsref->getUrl($version);
                case DefaultInventories::t3ts45:
                    return DefaultInventories::t3tsref->getUrl($version);
            }
        }
        return match ($this) {
            // Main doc page, it is only deployed to main
            DefaultInventories::t3docs => 'https://docs.typo3.org/',

            // Changelog, it is only deployed to main
            DefaultInventories::changelog => 'https://docs.typo3.org/c/typo3/cms-core/main/en-us/',

            // Core Manuals
            DefaultInventories::t3coreapi => 'https://docs.typo3.org/m/typo3/reference-coreapi/' . $version . '/en-us/',
            DefaultInventories::t3tca => 'https://docs.typo3.org/m/typo3/reference-tca/' . $version . '/en-us/',
            DefaultInventories::t3tsref => 'https://docs.typo3.org/m/typo3/reference-typoscript/' . $version . '/en-us/',
            DefaultInventories::t3viewhelper => 'https://docs.typo3.org/other/typo3/view-helper-reference/' . $version . '/en-us/',

            // Official Core Tutorials and Guides
            DefaultInventories::t3editors => 'https://docs.typo3.org/m/typo3/tutorial-editors/' . $version . '/en-us/',
            DefaultInventories::t3sitepackage => 'https://docs.typo3.org/m/typo3/tutorial-sitepackage/' . $version . '/en-us/',
            DefaultInventories::t3start => 'https://docs.typo3.org/m/typo3/tutorial-getting-started/' . $version . '/en-us/',
            DefaultInventories::t3translate => 'https://docs.typo3.org/m/typo3/guide-frontendlocalization/' . $version . '/en-us/',

            // Former Official manuals, redirected starting 12.4
            DefaultInventories::t3tsconfig => 'https://docs.typo3.org/m/typo3/reference-tsconfig/' . $version . '/en-us/',
            DefaultInventories::t3ts45 => 'https://docs.typo3.org/m/typo3/tutorial-typoscript-in-45-minutes/' . $version . '/en-us/',


            // Team Guides, they are commonly not versioned
            DefaultInventories::h2document => 'https://docs.typo3.org/m/typo3/docs-how-to-document/main/en-us/',
            DefaultInventories::t3content => 'https://docs.typo3.org/m/typo3/guide-contentandmarketing/main/en-us/',
            DefaultInventories::t3contribute => 'https://docs.typo3.org/m/typo3/guide-contributionworkflow/main/en-us/',
            DefaultInventories::t3writing => 'https://docs.typo3.org/m/typo3/writing-guide/main/en-us/',
            DefaultInventories::t3org => 'https://docs.typo3.org/m/typo3/team-t3oteam/main/en-us/',
            DefaultInventories::policy => 'https://docs.typo3.org/m/typo3/guide-policy/main/en-us/',
            DefaultInventories::guide_policy => 'https://docs.typo3.org/m/typo3/guide-policy/main/en-us/',

            // Other
            DefaultInventories::fluid => 'https://docs.typo3.org/other/typo3fluid/fluid/main/en-us/',
            DefaultInventories::t3renderguides => 'https://docs.typo3.org/other/t3docs/render-guides/main/en-us/',
            DefaultInventories::t3exceptions => 'https://docs.typo3.org/m/typo3/reference-exceptions/main/en-us/',

            DefaultInventories::api => 'https://api.typo3.org/' . $version . '/',
        };
    }

}
