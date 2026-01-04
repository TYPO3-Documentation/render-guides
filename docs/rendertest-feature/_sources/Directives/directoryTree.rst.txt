==============
Directory tree
==============

..  directory-tree::
    :level: 2
    :show-file-icons: true

    *   EXT:my_sitepackage/Resources/Private/Templates/

        *   Layouts

            *   Default.html
            *   WithoutHeader.html

        *   Pages

            *   Default.html
            *   StartPage.html
            *   TwoColumns.html
            *   With_sidebar.html

        *   Partials

            *   Footer.html
            *   Sidebar.html
            *   Menu.html


Directory tree with links
=========================

..  directory-tree::
    :level: 2
    :show-file-icons: true

    *   :doc:`Directory tree </Directives/directoryTree#directory-tree>`

        *   Layouts

            *   :doc:`Directory tree </Directives/directoryTree#directory-tree>`
            *   :doc:`Directory tree </Directives/directoryTree#directory-tree>`

        *   Pages

            *   :doc:`Directory tree </Directives/directoryTree#directory-tree>`
            *   :doc:`Directory tree </Directives/directoryTree#directory-tree>`

Directory structure of a typo3 extension
========================================

..  directory-tree::

    *   :file:`composer.json`
    *   :file:`ext_conf_template.txt`
    *   :file:`ext_emconf.php`
    *   :file:`ext_localconf.php`
    *   :file:`ext_tables.php`
    *   :file:`ext_tables.sql`
    *   :file:`ext_tables_static+adt.sql`
    *   :file:`ext_typoscript_constants.typoscript`
    *   :file:`ext_typoscript_setup.typoscript`
    *   :path:`Classes`
    *   :path:`Configuration`

        *   :path:`Backend`
        *   :path:`Extbase`

            *   :path:`Persistence`

        *   :path:`TCA`
        *   :path:`TsConfig`
        *   :path:`TypoScript`
        *   :file:`ContentSecurityPolicies.php`
        *   :file:`Icons.php`
        *   :file:`page.tsconfig`
        *   :file:`RequestMiddlewares.php`
        *   :file:`Services.yaml`
        *   :file:`user.tsconfig`

    *   :path:`Documentation`
    *   :path:`Resources`

        *   :path:`Private`

            *   :path:`Language`

        *   :path:`Public`

    *   :path:`Tests`
