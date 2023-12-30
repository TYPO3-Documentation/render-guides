..  include:: /Includes.rst.txt

..  _InterlinkRepositories:

=====================
Interlink Inventories
=====================

Sections in other manuals than the current one can be linked during rendering
by prefixing an anchor link or page link with the name of the manual.

Interlinks to official manuals
==============================

By using suffixes the version of the manual to be linked can be
specified:

..  code-block:: rst

    *   :ref:`TYPO3 Explained, preferred version <t3coreapi:start>`
    *   :ref:`TYPO3 Explained, main version (development) <t3coreapi_dev:start>`
    *   :ref:`TYPO3 Explained, stable version (for example 12.4) <t3coreapi_stable:start>`
    *   :ref:`TYPO3 Explained, old stable version (for example 11.5) <t3coreapi_oldstable:start>`

This would output:

*   :ref:`TYPO3 Explained, preferred version <t3coreapi:start>`
*   :ref:`TYPO3 Explained, main version (development) <t3coreapi_dev:start>`
*   :ref:`TYPO3 Explained, stable version (for example 12.4) <t3coreapi_stable:start>`
*   :ref:`TYPO3 Explained, old stable version (for example 11.5) <t3coreapi_oldstable:start>`

The preferred version can be set in the guides.xml to `dev`, `stable`,
`oldstable` or a specific minor version, for example `8.7`.

..  code-block:: xml
    :caption: Documentation/guides.xml

    <?xml version="1.0" encoding="UTF-8" ?>
    <guides
        xmlns="https://www.phpdoc.org/guides"
        xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
        xsi:schemaLocation="https://www.phpdoc.org/guides vendor/phpdocumentor/guides-cli/resources/schema/guides.xsd"
    >
        <project title="Render guides"/>
        <extension
            class="\T3Docs\Typo3DocsTheme\DependencyInjection\Typo3DocsThemeExtension"
            typo3-core-preferred="stable"
        />
    </guides>

or

..  code-block:: xml
    :caption: Documentation/guides.xml, excerpt

    <extension
        class="\T3Docs\Typo3DocsTheme\DependencyInjection\Typo3DocsThemeExtension"
        typo3-core-preferred="8.7"

It is not necessary anymore to list each of the standard inventories in the
guides.xml anymore. If desired you can override or redefine standard interlink
inventories or define new ones:

..  code-block:: xml
    :caption: Documentation/guides.xml

    <?xml version="1.0" encoding="UTF-8" ?>
    <guides
        xmlns="https://www.phpdoc.org/guides"
        xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
        xsi:schemaLocation="https://www.phpdoc.org/guides vendor/phpdocumentor/guides-cli/resources/schema/guides.xsd"
    >
        <project title="Render guides"/>
        <extension
            class="\T3Docs\Typo3DocsTheme\DependencyInjection\Typo3DocsThemeExtension"
            typo3-core-preferred="stable"
        />
        <!-- explicitly link to version 8.7 of TYPO3 Explained -->
        <inventory id="t3coreapi_v8" url="https://docs.typo3.org/m/typo3/reference-coreapi/8.7/en-us/"/>
        <!-- ext_sys_note and ext_sys_note_stable should always link to main-->
        <inventory id="ext_sys_note" url="https://docs.typo3.org/m/typo3/reference-coreapi/main/en-us/"/>
        <inventory id="ext_sys_note_stable" url="https://docs.typo3.org/m/typo3/reference-coreapi/main/en-us/"/>
    </guides>

The following links:

..  code-block:: rst

    *   :ref:`TYPO3 Explained, always version 8.7 <t3coreapi_v8:start>`
    *   :ref:`Sys note always goes to main <ext_sys_note:start>`
    *   :ref:`Sys note always goes to main <ext_sys_note_stable:start>`

*   :ref:`TYPO3 Explained, always version 8.7 <t3coreapi_v8:start>`
*   :ref:`Sys note always goes to main <ext_sys_note:start>`
*   :ref:`Sys note always goes to main <ext_sys_note_stable:start>`


Adding a new TYPO3 version or manual
------------------------------------

In the event of a change in long-term support, adjustments to the corresponding
TYPO3 versions can be made directly in the theme within the enum
:php:`\T3Docs\Typo3DocsTheme\Inventory\Typo3VersionMapping`.

The default manuals to be supported can be managed in enum
:php:`\T3Docs\Typo3DocsTheme\Inventory\DefaultInventories`.


Interlinks to TYPO3 extensions
==============================

You can link to the main version of the manual of a TYPO3 extension if
that extension's manual has been rendered on our server.

To create an interlink to a third-party extension prefix the extensions
composer name with `ext-` and replace the slash with `-` for example:

..  code-block:: rst

    *   :doc:`News <ext-georgringer/news:Index>`
    *   :ref:`External Imports <ext-cobweb/external_import:start>`

This will be rendered as:

*   :doc:`News <ext-georgringer/news:Index>`
*   :ref:`External Imports <ext-cobweb/external_import:start>`


If an extension author need to link to a specific version of an extensions manual,
they have to define that version manually in the manual's :file:`guides.xml`:

..  code-block:: xml
    :caption: Documentation/guides.xml

    <?xml version="1.0" encoding="UTF-8" ?>
    <guides
        xmlns="https://www.phpdoc.org/guides"
        xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
        xsi:schemaLocation="https://www.phpdoc.org/guides vendor/phpdocumentor/guides-cli/resources/schema/guides.xsd"
    >
        <project title="Render guides"/>
        <extension
            class="\T3Docs\Typo3DocsTheme\DependencyInjection\Typo3DocsThemeExtension"
            typo3-core-preferred="stable"
        />
        <!-- explicitly link to version 11.3 of the news manual -->
        <inventory id="ext-georgringer-news-11-3" url="https://docs.typo3.org/p/georgringer/news/11.3/en-us/"/>
    </guides>

..  code-block:: rst

    *   :doc:`News <ext-georgringer/news-11-3:Index>`

This will be rendered as:

*   :doc:`News <ext-georgringer/news-11-3:Index>`
