===========
TYPO3 Files
===========

..  typo3:file:: ext_tables.sql
    :path: /
    :scope: extension
    :regex: /^.*ext_tables\.sql$/

    This file should contain a table-structure dump of the tables used by the
    extension which are not auto-generated.

..  typo3:file:: page.tsconfig
    :path: Configuration/
    :scope: extension
    :regex: ~^.*Configuration/page\.tsconfig$~

    In this file global page TSconfig can be stored. It will be automatically
    included for all pages.

..  typo3:file:: settings.php
    :composerPath: /config/system/
    :classicPath: /typo3conf/system/
    :scope: project
    :regex: /^.*settings\.php$/

    In this file global page TSconfig can be stored. It will be automatically
    included for all pages.

..  typo3:file:: ENABLE_INSTALL_TOOL
    :composerPath: /config/
    :classicPath: /typo3conf/
    :scope: project
    :language: plaintext
    :regex: /^.*ENABLE\_INSTALL\_TOOL$/

    When this file is set, it allows access to the TYPO3 Install Tool.

..  typo3:file:: LOCK_BACKEND
    :composerPath: /var/lock/
    :classicPath: /config/
    :scope: project
    :language: plaintext
    :configuration: :php:`$GLOBALS['TYPO3_CONF_VARS']['BE']['lockBackendFile']`
    :command: `vendor/bin/typo3 backend:lock`, `vendor/bin/typo3 backend:unlock`
    :regex: /^.*LOCK\_BACKEND$/

    When this file is set, it allows access to the TYPO3 Install Tool.

..  typo3:file:: config.yaml
    :scope: set
