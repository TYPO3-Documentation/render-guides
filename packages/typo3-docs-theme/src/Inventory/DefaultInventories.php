<?php

namespace T3Docs\Typo3DocsTheme\Inventory;

enum DefaultInventories: string
{
    // Changelog, it is only deployed to main
    case ext_core = 'https://docs.typo3.org/c/typo3/cms-core/main/en-us/';

    // Core Manuals
    case t3coreapi = 'https://docs.typo3.org/m/typo3/reference-coreapi/{typo3_version}/en-us/';
    case t3tca = 'https://docs.typo3.org/m/typo3/reference-tca/{typo3_version}/en-us/';
    case t3tsconfig = 'https://docs.typo3.org/m/typo3/reference-tsconfig/{typo3_version}/en-us/';
    case t3tsref = 'https://docs.typo3.org/m/typo3/reference-typoscript/{typo3_version}/en-us/';
    case t3viewhelper = 'https://docs.typo3.org/other/typo3/view-helper-reference/{typo3_version}/en-us/';

    // Official Core Tutorials and Guides
    case t3editors = 'https://docs.typo3.org/m/typo3/tutorial-editors/{typo3_version}/en-us/';
    case t3install = 'https://docs.typo3.org/m/typo3/guide-installation/{typo3_version}/en-us/';
    case t3sitepackage = 'https://docs.typo3.org/m/typo3/tutorial-sitepackage/{typo3_version}/en-us/';
    case t3start = 'https://docs.typo3.org/m/typo3/tutorial-getting-started/{typo3_version}/en-us/';
    case t3translate = 'https://docs.typo3.org/m/typo3/guide-frontendlocalization/{typo3_version}/en-us/';
    case t3ts45 = 'https://docs.typo3.org/m/typo3/tutorial-typoscript-in-45-minutes/{typo3_version}/en-us/';

    // Team Guides, they are commonly not versioned
    case h2document = 'https://docs.typo3.org/m/typo3/docs-how-to-document/main/en-us/';
    case t3content = 'https://docs.typo3.org/m/typo3/guide-contentandmarketing/main/en-us/';
    case t3contribute = 'https://docs.typo3.org/m/typo3/guide-contributionworkflow/main/en-us/';

    // System Extensions
    case ext_adminpanel = 'https://docs.typo3.org/c/typo3/cms-adminpanel/{typo3_version}/en-us/';
    case ext_dashboard = 'https://docs.typo3.org/c/typo3/cms-dashboard/{typo3_version}/en-us/';
    case ext_felogin = 'https://docs.typo3.org/c/typo3/cms-felogin/{typo3_version}/en-us/';
    case ext_form = 'https://docs.typo3.org/c/typo3/cms-form/{typo3_version}/en-us/';
    case ext_fsc = 'https://docs.typo3.org/c/typo3/cms-fluid-styled-content/{typo3_version}/en-us/';
    case ext_impexp = 'https://docs.typo3.org/c/typo3/cms-impexp/{typo3_version}/en-us/';
    case ext_indexed_search = 'https://docs.typo3.org/c/typo3/cms-indexed-search/{typo3_version}/en-us/';
    case ext_linkvalidator = 'https://docs.typo3.org/c/typo3/cms-linkvalidator/{typo3_version}/en-us/';
    case ext_lowlevel = 'https://docs.typo3.org/c/typo3/cms-lowlevel/{typo3_version}/en-us/';
    case ext_reactions = 'https://docs.typo3.org/c/typo3/cms-reactions/{typo3_version}/en-us/';
    case ext_reports = 'https://docs.typo3.org/c/typo3/cms-reports/{typo3_version}/en-us/';
    case ext_rte_ckeditor = 'https://docs.typo3.org/c/typo3/cms-rte-ckeditor/{typo3_version}/en-us/';
    case ext_scheduler = 'https://docs.typo3.org/c/typo3/cms-scheduler/{typo3_version}/en-us/';
    case ext_seo = 'https://docs.typo3.org/c/typo3/cms-seo/{typo3_version}/en-us/';
    case ext_sys_note = 'https://docs.typo3.org/c/typo3/cms-sys-note/{typo3_version}/en-us/';
    case ext_workspaces = 'https://docs.typo3.org/c/typo3/cms-workspaces/{typo3_version}/en-us/';

    // Other
    case fluid = 'https://docs.typo3.org/other/typo3fluid/fluid/{typo3_version}/en-us/';

}
