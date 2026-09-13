..  include:: /Includes.rst.txt

..  _Directives:

==========
Directives
==========

..  rst-class:: compact-list
..  toctree::
    :glob:

    *

..  _directive-overview:

Renderer coverage
=================

..  note::

    This is **not** a guide to writing reStructuredText. For how to use a
    directive, see the `reStructuredText reference in "How to document TYPO3"
    <https://docs.typo3.org/m/typo3/docs-how-to-document/main/en-us/Reference/ReStructuredText/Index.html>`__.

    The tables below answer a different question: **which directives this
    renderer supports, and which page of this rendering test renders them.**
    A row whose :guilabel:`Rendered on` cell says `none` is a gap — the
    directive works, but nothing here would catch it if the theme broke it.

The :guilabel:`From` column uses these abbreviations:

rst
    `phpdocumentor/guides-restructured-text` — the core reStructuredText set.
bootstrap
    `phpdocumentor/guides-theme-bootstrap`.
graphs
    `phpdocumentor/guides-graphs`.
php-domain
    `t3docs/guides-php-domain`.
console
    `t3docs/console-command`.
theme
    Defined locally in :file:`packages/typo3-docs-theme`.
template
    No PHP class at all — the name falls through to the generic directive
    handler and is rendered by :file:`body/directive/<name>.html.twig`.
    Nothing fails loudly if such a template is renamed.

Content and admonitions
-----------------------

..  csv-table::
    :header: "Directive", "From", "Rendered on"
    :widths: 32, 20, 48

    "``note``, ``tip``, ``hint``, ``important``", "rst", ":doc:`/Admonitions-and-buttons/Index`"
    "``warning``, ``caution``, ``danger``, ``error``", "rst", ":doc:`/Admonitions-and-buttons/Index`"
    "``attention``, ``seealso``, ``todo``", "rst", ":doc:`/Admonitions-and-buttons/Index`"
    "``admonition``", "rst", ":doc:`/Admonitions-and-buttons/Index`"
    "plain block quote (indentation)", "rst", ":doc:`/Blockquotes/Index`"
    "``epigraph``, ``highlights``, ``pull-quote``", "rst", "none"
    "``rubric``", "template", ":doc:`/ThisAndThat/Index`"
    "``sidebar``", "rst", ":doc:`/ThisAndThat/Index`"
    "``container`` / ``div``", "rst", ":doc:`/Cards/Index`"
    "``class`` / ``rst-class``", "rst", ":doc:`/ThisAndThat/Index`"
    "``math``", "rst", ":doc:`/ThisAndThat/Index`"
    "``hlist``", "template", ":doc:`/ThisAndThat/Index`"
    "``glossary``", "theme", ":doc:`/Glossary/Index`"
    "``replace``", "rst", "none"
    "``topic``", "template", "none"
    "``wrap``", "template", "none"
    "``only``", "template", "none"
    "``documentblock``", "rst", "none"

Code and configuration values
-----------------------------

..  csv-table::
    :header: "Directive", "From", "Rendered on"
    :widths: 32, 20, 48

    "``code-block`` / ``code`` / ``parsed-literal``", "rst", ":doc:`/Codeblocks/Index`"
    "``literalinclude``", "theme, overrides rst", ":doc:`/SiteSettings/Index`"
    "``include``", "theme, overrides rst", "used by every page for :file:`Includes.rst.txt`"
    "``highlight``", "rst", ":doc:`/Lineblocks/Index`"
    "``confval``", "rst", ":doc:`/Confval/Index`"
    "``confval-menu``", "theme", ":doc:`/Confval/Index`"
    "``option``", "rst", ":doc:`/Directives/option`"
    "``typo3:site-set-settings``", "theme", ":doc:`/SiteSettings/Index`"
    "``configuration-block``", "rst", "none"
    "``guides:codeblock-languages``", "template", "none"

Tables and lists
----------------

..  csv-table::
    :header: "Directive", "From", "Rendered on"
    :widths: 32, 20, 48

    "``table``", "rst", ":doc:`/Tables/TableDirective`"
    "``csv-table``", "rst", ":doc:`/Tables/CsvTable`"
    "``list-table``", "rst", ":doc:`/ImagesAndFigures/Zoom`"
    "``t3-field-list-table``", "theme", ":doc:`/Tables/Index`"
    "``directory-tree``", "theme", ":doc:`/Directives/directoryTree`"

Layout components
-----------------

..  csv-table::
    :header: "Directive", "From", "Rendered on"
    :widths: 32, 20, 48

    "``card``, ``card-grid``, ``card-group``", "bootstrap", ":doc:`/Cards/Index`"
    "``card-header``, ``card-image``, ``card-footer``", "bootstrap", ":doc:`/Cards/Index`"
    "``accordion``, ``accordion-item``", "bootstrap", ":doc:`/Accordion/Index`"
    "``tabs``, ``tab``", "rst", ":doc:`/Tabs/Index`"
    "``group-tab``", "theme", ":doc:`/Tabs/Index`"

Media and diagrams
------------------

..  csv-table::
    :header: "Directive", "From", "Rendered on"
    :widths: 32, 20, 48

    "``image``", "rst", ":doc:`/ImagesAndFigures/Index`"
    "``figure``", "theme, replaces rst", ":doc:`/ImagesAndFigures/Index`"
    "``youtube``", "theme, overrides rst", ":doc:`/Directives/youtube`"
    "``uml``", "graphs", ":doc:`/Uml/Index`"
    "``graphviz``", "template", "none"

Navigation and page metadata
----------------------------

..  csv-table::
    :header: "Directive", "From", "Rendered on"
    :widths: 32, 20, 48

    "``toctree``", "rst", ":doc:`/Nested-pages/Index`"
    "``menu``", "rst", ":doc:`/Directives/menu`"
    "``contents``", "rst", "used at the top of many pages"
    "``index``", "rst", ":doc:`/Tabs/Index`"
    "``role``, ``default-role``", "rst", ":doc:`/Inline-code-and-textroles/Index`"
    "``versionadded``, ``versionchanged``, ``deprecated``", "rst", ":doc:`/Directives/versionadded`"
    "``title``", "rst", "none"
    "``meta``", "rst", "none"
    "``breadcrumb``", "rst", "none"
    "``sectionauthor`` / ``codeauthor``", "rst", "none"
    "``guides:inventories``", "template", "none"

PHP domain
----------

All of these are demonstrated on :doc:`/PhpDomain/Index`.

..  csv-table::
    :header: "Directive", "From"
    :widths: 50, 50

    "``php:namespace``", "php-domain"
    "``php:class``", "php-domain"
    "``php:interface``", "php-domain"
    "``php:trait``", "php-domain"
    "``php:enum``", "php-domain"
    "``php:case``", "php-domain"
    "``php:exception``", "php-domain"
    "``php:method``", "php-domain"
    "``php:staticmethod``", "php-domain"
    "``php:property`` / ``php:attr``", "php-domain"
    "``php:const``", "php-domain"
    "``php:global``", "php-domain"

TYPO3 specific
--------------

..  csv-table::
    :header: "Directive", "From", "Rendered on"
    :widths: 32, 20, 48

    "``typo3:file``", "theme", ":doc:`/Typo3File/Index`"
    "``typo3:viewhelper``", "theme", ":doc:`/ViewHelpers/Index`"
    "``console:command``", "console", ":doc:`/ConsoleCommands/Index`"
    "``console:command-list``", "console", ":doc:`/ConsoleCommands/ListAll`"

Linkable objects that are not directives
----------------------------------------

These are emitted by a parent directive, one per entry in its JSON source. They
are never written by hand, but they own anchors.

..  csv-table::
    :header: "Object", "Anchor prefix", "Emitted by", "Rendered on"
    :widths: 26, 24, 22, 28

    "``console:argument``", "``console-argument-``", "``console:command``", ":doc:`/ConsoleCommands/Index`"
    "``console:option``", "``console-option-``", "``console:command``", ":doc:`/ConsoleCommands/Index`"
    "``typo3:viewhelper-argument``", "``viewhelper-argument-``", "``typo3:viewhelper``", ":doc:`/ViewHelpers/Index`"

Not available
-------------

Directives that exist in the dependencies but cannot be used in TYPO3
documentation, listed so that nobody goes looking for them:

``raw``
    Disabled on purpose by the theme for security reasons. Using it logs an
    error and renders nothing.
``latex-main``
    Only meaningful for LaTeX output.
``testlogger``
    A test fixture of the upstream package.
``main-menu-json``
    Internal, used only to build the main menu of `docs.typo3.org`.
``typo3:talk``
    Internal, filled by a node transformer.
