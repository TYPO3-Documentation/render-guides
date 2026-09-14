..  include:: /Includes.rst.txt
..  _typo3-file:

==========
typo3:file
==========

`..  typo3:file::` documents a file and its location, showing the path both
for Composer-based and Classic mode installations.

`:file:` is a text role to create a reference to such a file description.

Two options produce no output in the block below, but should still always be
set — both feed the popover that opens when a `:file:` link is clicked:
`:shortDescription:` is the text shown in it, and `:regex:` is the pattern that
matches a `:file:` reference to this definition.

Use either `:path:` or the pair `:composerPath:` / `:classicPath:`, not both:
`:path:` is only a fallback for the two, but it is also appended to the anchor,
so combining them produces a duplicated path segment.


Demo 1 - the usual shape
========================

Composer and Classic installations put this file in different places, so both
paths are given explicitly.

Source:

..  code-block:: rst

    ..  typo3:file:: settings.php
        :scope: project
        :composerPath: config/system/
        :classicPath: typo3conf/system/
        :regex: /^.*settings\.php$/
        :shortDescription: Contains system-wide settings such as database credentials.

        The most important configuration file. It contains local settings in the
        global array ``$GLOBALS['TYPO3_CONF_VARS']``.

Result:

..  typo3:file:: settings.php
    :scope: project
    :composerPath: config/system/
    :classicPath: typo3conf/system/
    :regex: /^.*settings\.php$/
    :shortDescription: Contains system-wide settings such as database credentials.

    The most important configuration file. It contains local settings in the
    global array ``$GLOBALS['TYPO3_CONF_VARS']``.


Demo 2 - scope extension
=========================

With `:scope: extension` both paths are prefixed automatically, so a single
`:path:` is enough.

..  typo3:file:: ext_tables.sql
    :path: /
    :scope: extension
    :regex: /^.*ext_tables\.sql$/
    :shortDescription: Table-structure dump of the tables used by the extension.

    This file should contain a table-structure dump of the tables used by the
    extension which are not auto-generated.


Demo 3 - scope set
====================

`:scope: set` prefixes the paths for a TYPO3 Set instead. Any other value, such
as `project` or `site`, is shown as-is and adds no prefix.

..  typo3:file:: config.yaml
    :scope: set
    :regex: /^.*config\.yaml$/
    :shortDescription: Configuration for a single set.

    Configuration for a single set.


Demo 4 - configuration and command
==================================

`:configuration:` and `:command:` add two more rows to the block. Both are
parsed as inline reStructuredText, so text roles work inside them.

..  typo3:file:: LOCK_BACKEND
    :scope: project
    :composerPath: var/lock/
    :classicPath: config/
    :regex: /^.*LOCK\_BACKEND$/
    :configuration: :php:`$GLOBALS['TYPO3_CONF_VARS']['BE']['lockBackendFile']`
    :command: `vendor/bin/typo3 backend:lock`, `vendor/bin/typo3 backend:unlock`
    :shortDescription: When present, the TYPO3 backend is locked.

    When this file is present, access to the backend is blocked.


Demo 5 - noindex
==================

..  _typo3-file-with-noindex:

..  typo3:file:: additional.php
    :scope: project
    :composerPath: config/system/
    :classicPath: typo3conf/system/
    :regex: /^.*additional\.php$/
    :noindex:
    :shortDescription: Overrides settings.php, never touched by TYPO3.

    Custom code should be placed here.

A `:noindex:` file gets no anchor, so a `:file:` reference to it renders as
plain text instead of a link — silently, without a warning. Link to the section
above it instead: :ref:`typo3-file-with-noindex`.

Example of plaintext rendering: :file:`additional.php`

Linking files
==============

Clicking one of these opens the popover built from `:shortDescription:`.

*   :file:`settings.php`
*   :file:`ext_tables.sql`
*   :file:`config.yaml`
*   :file:`LOCK_BACKEND`
