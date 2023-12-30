<?php

namespace T3Docs\Typo3DocsTheme\Inventory;

enum DefaultInventories: string
{
    case changelog = 'changelog';
    case t3coreapi = 't3coreapi';
    case t3tca = 't3tca';
    case t3tsconfig = 't3tsconfig';
    case t3tsref = 't3tsref';
    case t3viewhelper = 't3viewhelper';
    case t3editors = 't3editors';
    case t3install = 't3install';
    case t3sitepackage = 't3sitepackage';
    case t3start = 't3start';
    case t3translate = 't3translate';
    case t3ts45 = 't3ts45';
    case h2document = 'h2document';
    case t3content = 't3content';
    case t3contribute = 't3contribute';
    case fluid = 'fluid';

    public function getUrl(): string
    {
        return match ($this) {
            // Changelog, it is only deployed to main
            DefaultInventories::changelog => 'https://docs.typo3.org/c/typo3/cms-core/main/en-us/',

            // Core Manuals
            DefaultInventories::t3coreapi => 'https://docs.typo3.org/m/typo3/reference-coreapi/{typo3_version}/en-us/',
            DefaultInventories::t3tca => 'https://docs.typo3.org/m/typo3/reference-tca/{typo3_version}/en-us/',
            DefaultInventories::t3tsconfig => 'https://docs.typo3.org/m/typo3/reference-tsconfig/{typo3_version}/en-us/',
            DefaultInventories::t3tsref => 'https://docs.typo3.org/m/typo3/reference-typoscript/{typo3_version}/en-us/',
            DefaultInventories::t3viewhelper => 'https://docs.typo3.org/other/typo3/view-helper-reference/{typo3_version}/en-us/',

            // Official Core Tutorials and Guides
            DefaultInventories::t3editors => 'https://docs.typo3.org/m/typo3/tutorial-editors/{typo3_version}/en-us/',
            DefaultInventories::t3install => 'https://docs.typo3.org/m/typo3/guide-installation/{typo3_version}/en-us/',
            DefaultInventories::t3sitepackage => 'https://docs.typo3.org/m/typo3/tutorial-sitepackage/{typo3_version}/en-us/',
            DefaultInventories::t3start => 'https://docs.typo3.org/m/typo3/tutorial-getting-started/{typo3_version}/en-us/',
            DefaultInventories::t3translate => 'https://docs.typo3.org/m/typo3/guide-frontendlocalization/{typo3_version}/en-us/',
            DefaultInventories::t3ts45 => 'https://docs.typo3.org/m/typo3/tutorial-typoscript-in-45-minutes/{typo3_version}/en-us/',

            // Team Guides, they are commonly not versioned
            DefaultInventories::h2document => 'https://docs.typo3.org/m/typo3/docs-how-to-document/main/en-us/',
            DefaultInventories::t3content => 'https://docs.typo3.org/m/typo3/guide-contentandmarketing/main/en-us/',
            DefaultInventories::t3contribute => 'https://docs.typo3.org/m/typo3/guide-contributionworkflow/main/en-us/',

            // Other
            DefaultInventories::fluid => 'https://docs.typo3.org/other/typo3fluid/fluid/{typo3_version}/en-us/',
        };
    }

}
