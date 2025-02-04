===========
TYPO3 Files
===========

..  typo3:file:: ext_tables.sql
    :path: /
    :scope: extension
    :regex: /^.*ext_tables\.sql$/
    :shortDescription: Table-structure dump of the tables used by the extension

    This file should contain a table-structure dump of the tables used by the
    extension which are not auto-generated.

..  typo3:file:: page.tsconfig
    :path: Configuration/
    :scope: extension
    :regex: ~^.*Configuration/page\.tsconfig$~
    :shortDescription: global page TSconfig

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
    :regex: /.*Configuration\/Sets\/[^\/]+\/config\.yaml$/

..  typo3:file:: SomeClass.php
    :name: classes-someclass-php
    :scope: extension
    :path: /Classes/
    :regex: /^.*Classes\/.*\.php/
    :shortDescription: PHP Classes in this file get auto-loaded

Linking files
=============

*   :file:`config/ENABLE_INSTALL_TOOL`
*   :file:`Configuration/Sets/MySet/config.yaml`
*   :file:`settings.php`
*   :file:`ext_tables.sql`
*   :file:`Classes/SomeClass.php`
*   :file:`EXT:my_extension/Classes/SomeClass.php`
*   :file:`Configuration File <Configuration/Sets/MySet/config.yaml>`
*   :file:`Unknown/File.xyz`
*   :file:`FILE:EXT:Unknown/File.xyz`
*   :file:`SomeClass.php <extension-classes-someclass-php>`

Code Block captions
===================

..  code-block:: php
    :caption: Classes/SomeClass.php

    echo 'Hello, TYPO3';

..  code-block:: php
    :caption: :file:`Classes/SomeClass.php`

    echo 'Hello, TYPO3';

