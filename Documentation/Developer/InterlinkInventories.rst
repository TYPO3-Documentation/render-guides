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

..  _available-default-inventories:

Available default inventories
-----------------------------

These inventories can be used by default in any rendered documentation:


*   Title: :doc:`t3docs:Index`

    Inventory key: :doc:`t3docs <t3docs:Index>`

    URL: https://docs.typo3.org/

*   Title: :doc:`changelog:Index`

    Inventory key: :doc:`changelog <changelog:Index>`

    URL: https://docs.typo3.org/c/typo3/cms-core/main/en-us/

*   Title: :doc:`t3coreapi:Index`

    Inventory key: :doc:`t3coreapi <t3coreapi:Index>`

    URL: https://docs.typo3.org/m/typo3/reference-coreapi/main/en-us/

*   Title: :doc:`t3tca:Index`

    Inventory key: :doc:`t3tca <t3tca:Index>`

    URL: https://docs.typo3.org/m/typo3/reference-tca/main/en-us/

*   Title: :doc:`t3tsconfig:Index`

    Inventory key: :doc:`t3tsconfig <t3tsconfig:Index>`

    URL: https://docs.typo3.org/m/typo3/reference-tsconfig/main/en-us/

*   Title: :doc:`t3tsref:Index`

    Inventory key: :doc:`t3tsref <t3tsref:Index>`

    URL: https://docs.typo3.org/m/typo3/reference-typoscript/main/en-us/

*   Title: :doc:`t3viewhelper:Index`

    Inventory key: :doc:`t3viewhelper <t3viewhelper:Index>`

    URL: https://docs.typo3.org/other/typo3/view-helper-reference/main/en-us/

*   Title: :doc:`t3editors:Index`

    Inventory key: :doc:`t3editors <t3editors:Index>`

    URL: https://docs.typo3.org/m/typo3/tutorial-editors/main/en-us/

*   Title: :doc:`t3install:Index`

    Inventory key: :doc:`t3install <t3install:Index>`

    URL: https://docs.typo3.org/m/typo3/guide-installation/main/en-us/

*   Title: :doc:`t3upgrade:Index`

    Inventory key: :doc:`t3upgrade <t3upgrade:Index>`

    URL: https://docs.typo3.org/m/typo3/guide-installation/main/en-us/

*   Title: :doc:`t3sitepackage:Index`

    Inventory key: :doc:`t3sitepackage <t3sitepackage:Index>`

    URL: https://docs.typo3.org/m/typo3/tutorial-sitepackage/main/en-us/

*   Title: :doc:`t3start:Index`

    Inventory key: :doc:`t3start <t3start:Index>`

    URL: https://docs.typo3.org/m/typo3/tutorial-getting-started/main/en-us/

*   Title: :doc:`t3translate:Index`

    Inventory key: :doc:`t3translate <t3translate:Index>`

    URL: https://docs.typo3.org/m/typo3/guide-frontendlocalization/main/en-us/

*   Title: :doc:`t3ts45:Index`

    Inventory key: :doc:`t3ts45 <t3ts45:Index>`

    URL: https://docs.typo3.org/m/typo3/tutorial-typoscript-in-45-minutes/main/en-us/

*   Title: :doc:`h2document:Index`

    Inventory key: :doc:`h2document <h2document:Index>`

    URL: https://docs.typo3.org/m/typo3/docs-how-to-document/main/en-us/

*   Title: :doc:`t3content:Index`

    Inventory key: :doc:`t3content <t3content:Index>`

    URL: https://docs.typo3.org/m/typo3/guide-contentandmarketing/main/en-us/

*   Title: :doc:`t3contribute:Index`

    Inventory key: :doc:`t3contribute <t3contribute:Index>`

    URL: https://docs.typo3.org/m/typo3/guide-contributionworkflow/main/en-us/

*   Title: :doc:`t3writing:Index`

    Inventory key: :doc:`t3writing <t3writing:Index>`

    URL: https://docs.typo3.org/m/typo3/writing-guide/main/en-us/

*   Title: :doc:`t3org:Index`

    Inventory key: :doc:`t3org <t3org:Index>`

    URL: https://docs.typo3.org/m/typo3/writing-guide/main/en-us/

*   Title: :doc:`fluid:Index`

    Inventory key: :doc:`fluid <fluid:Index>`

    URL: https://docs.typo3.org/other/typo3fluid/fluid/main/en-us/

*   Title: :doc:`t3renderguides:Index`

    Inventory key: :doc:`t3renderguides <t3renderguides:Index>`

    URL: https://docs.typo3.org/other/t3docs/render-guides/main/en-us/

*   Title: :doc:`t3exceptions:Index`

    Inventory key: :doc:`t3exceptions <t3exceptions:Index>`

    URL: https://docs.typo3.org/typo3cms/exceptions/main/en-us/


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
    *   :ref:`RTE <typo3/cms-rte-ckeditor:introduction>`

This will be rendered as:

*   :doc:`Adminpanel <typo3/cms-adminpanel:Index>`
*   :ref:`RTE <typo3/cms-rte-ckeditor:introduction>`

By default they will link to :ref:`your preferred TYPO3 version <typo3-version>`.
You can link to another version by using the same prefixes as for official
manuals:

..  code-block:: rst

    *   :ref:`RTE <typo3/cms-rte-ckeditor/dev:introduction>`
    *   :ref:`RTE <typo3/cms-rte-ckeditor/stable:introduction>`
    *   :ref:`RTE <typo3/cms-rte-ckeditor/oldstable:introduction>`
    *   :ref:`RTE <typo3/cms-rte-ckeditor/v12:introduction>`
    *   :ref:`RTE <typo3/cms-rte-ckeditor/12.4:introduction>`
    *   :ref:`RTE <typo3/cms-rte-ckeditor/v8:introduction>`
    *   :ref:`RTE <typo3/cms-rte-ckeditor/8.7:introduction>`

This will be rendered as:

*   :ref:`RTE <typo3/cms-rte-ckeditor/dev:introduction>`
*   :ref:`RTE <typo3/cms-rte-ckeditor/stable:introduction>`
*   :ref:`RTE <typo3/cms-rte-ckeditor/oldstable:introduction>`
*   :ref:`RTE <typo3/cms-rte-ckeditor/v12:introduction>`
*   :ref:`RTE <typo3/cms-rte-ckeditor/12.4:introduction>`
*   :ref:`RTE <typo3/cms-rte-ckeditor/v8:introduction>`
*   :ref:`RTE <typo3/cms-rte-ckeditor/8.7:introduction>`

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
