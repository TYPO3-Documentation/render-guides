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
    *   :ref:`TYPO3 Explained, main version (development) <t3coreapi/dev:start>`
    *   :ref:`TYPO3 Explained, stable version (for example 12.4) <t3coreapi/stable:start>`
    *   :ref:`TYPO3 Explained, old stable version (for example 11.5) <t3coreapi/oldstable:start>`
    *   :ref:`TYPO3 Explained 12 LTS <t3coreapi/v12:start>`
    *   :ref:`TYPO3 Explained 12.4 <t3coreapi/12.4:start>`

This would output:

*   :ref:`TYPO3 Explained, preferred version <t3coreapi:start>`
*   :ref:`TYPO3 Explained, main version (development) <t3coreapi/dev:start>`
*   :ref:`TYPO3 Explained, stable version (for example 12.4) <t3coreapi/stable:start>`
*   :ref:`TYPO3 Explained, old stable version (for example 11.5) <t3coreapi/oldstable:start>`
*   :ref:`TYPO3 Explained 12 LTS <t3coreapi/v12:start>`
*   :ref:`TYPO3 Explained 12.4 <t3coreapi/12.4:start>`

..  _typo3-version:

Setting the preferred TYPO3 version
-----------------------------------

The preferred version can be set in the guides.xml to `dev`, `stable`,
`oldstable` or a specific minor version, for example `8.7` or `v8`.

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
:file:`guides.xml` anymore. If desired you can override or redefine standard interlink
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
    </guides>

The following link:

..  code-block:: rst

    *   :ref:`TYPO3 Explained, always version 8.7 <t3coreapi_v8:start>`

*   :ref:`TYPO3 Explained, always version 8.7 <t3coreapi_v8:start>`


Adding a new TYPO3 version or manual
------------------------------------

In the event of a change in long-term support, adjustments to the corresponding
TYPO3 versions can be made directly in the theme within the enum
:php:`\T3Docs\Typo3DocsTheme\Inventory\Typo3VersionMapping`.

The default manuals to be supported can be managed in enum
:php:`\T3Docs\Typo3DocsTheme\Inventory\DefaultInventories`.


Interlinks to system extensions
===============================

You can link to the manual of a system extension: Use the extension's
Composer name as interlink domain:

..  code-block:: rst

    *   :doc:`Adminpanel <typo3/cms-adminpanel:Index>`
    *   :ref:`RTE <typo3/cms-rte-ckeditor:start>`

This will be rendered as:

*   :doc:`Adminpanel <typo3/cms-adminpanel:Index>`
*   :ref:`RTE <typo3/cms-rte-ckeditor:start>`

By default they will link to :ref:`your preferred TYPO3 version <typo3-version>`.
You can link to another version by using the same prefixes as for official
manuals:

..  code-block:: rst

    *   :ref:`RTE <typo3/cms-rte-ckeditor/dev:start>`
    *   :ref:`RTE <typo3/cms-rte-ckeditor/stable:start>`
    *   :ref:`RTE <typo3/cms-rte-ckeditor/oldstable:start>`
    *   :ref:`RTE <typo3/cms-rte-ckeditor/v12:start>`
    *   :ref:`RTE <typo3/cms-rte-ckeditor/12.4:start>`
    *   :ref:`RTE <typo3/cms-rte-ckeditor/v8:start>`
    *   :ref:`RTE <typo3/cms-rte-ckeditor/8.7:start>`

This will be rendered as:

*   :ref:`RTE <typo3/cms-rte-ckeditor/dev:start>`
*   :ref:`RTE <typo3/cms-rte-ckeditor/stable:start>`
*   :ref:`RTE <typo3/cms-rte-ckeditor/oldstable:start>`
*   :ref:`RTE <typo3/cms-rte-ckeditor/v12:start>`
*   :ref:`RTE <typo3/cms-rte-ckeditor/12.4:start>`
*   :ref:`RTE <typo3/cms-rte-ckeditor/v8:start>`
*   :ref:`RTE <typo3/cms-rte-ckeditor/8.7:start>`

For your convenience the changelog, situated in system extension `typo3/cms-core`
can also be linked with the prefix `changelog`:

..  code-block:: rst

    *   :ref:`Changelog: Remove jquery-ui <changelog:breaking-100966-1686062649>`

This will be rendered as:

*   :ref:`Changelog: Remove jquery-ui <changelog:breaking-100966-1686062649>`


Interlinks to third-party extensions
====================================

You can link to the manual of a third-party extension if
that extension's manual has been rendered on https://docs.typo3.org.

To create an interlink to a third-party extension, use the extension's
Composer name as interlink domain:

..  code-block:: rst

    *   :doc:`News <georgringer/news:Index>`
    *   :ref:`External Imports <cobweb/external_import:start>`

This will be rendered as:

*   :doc:`News <georgringer/news:Index>`
*   :ref:`External Imports <cobweb/external_import:start>`

By default this will link to the main version of the manual. If you desire to
link a specific version, you can attach the minor version (for example "11.3")
separated by a slash:

..  code-block:: rst

    *   :doc:`News <georgringer/news/11.3:Index>`
    *   :doc:`External Imports <cobweb/external_import/7.2:Index>`

*   :doc:`News <georgringer/news/11.3:Index>`
*   :doc:`External Imports <cobweb/external_import/7.2:Index>`

If an extension author needs to link to a specific version of an extension's manual,
they can define that version manually in the manual's :file:`guides.xml`:

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
        <!-- explicitly link to stable versions of extensions -->
        <inventory id="georgringer/news/stable" url="https://docs.typo3.org/p/georgringer/news/11.3/en-us/"/>
        <inventory id="georgringer/news/stable" url="https://docs.typo3.org/p/cobweb/external_import/7.2/en-us/"/>
    </guides>

..  code-block:: rst

    *   :doc:`News <georgringer/news/stable:Index>`
    *   :doc:`External Imports <cobweb/external_import/stable:Index>`

This will be rendered as:

*   :doc:`News <georgringer/news/stable:Index>`
*   :doc:`External Imports <cobweb/external_import/stable:Index>`
