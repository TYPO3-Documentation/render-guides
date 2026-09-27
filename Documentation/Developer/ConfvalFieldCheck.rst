..  include:: /Includes.rst.txt

..  _ConfvalFieldCheck:

=======================
Checking confval fields
=======================

A field of :rst:`..  confval::` is matched by its exact name. So
:rst:`:Default:`, :rst:`:default:` and :rst:`:de-fault:` are three different
fields, and only :rst:`:default:` is the default the directive knows. The
others fall through without a word: they are shown as fields of their own,
listed in :file:`confvals.json` under their own name, and
empty as a column of a :rst:`..  confval-menu::`.

A manual can name the fields it uses, and the rendering warns about every
other one:

..  code-block:: xml
    :caption: Documentation/guides.xml

    <extension class="\T3Docs\Typo3DocsTheme\DependencyInjection\Typo3DocsThemeExtension"
               interlink-shortcode="t3tca"
               confval-fields="TCA path, Scope, Types, RenderType"
    />

The names are separated by commas and may contain spaces. The check is off
until a manual sets them, and a warning fails a render with
:bash:`--minimal-test`, which is what the pipelines of the manuals run.

What is checked
===============

..  rst-class:: dl-parameters

Fields of :rst:`..  confval::`
    Every field that is neither declared nor one of those always allowed.

Columns of :rst:`..  confval-menu::`
    Every column that names such a field, since it would stay empty.

Always allowed are the fields the directive reads itself, :rst:`:name:`,
:rst:`:type:`, :rst:`:default:`, :rst:`:required:` and :rst:`:noindex:`, and
those the theme reads for the search, :rst:`:searchFacet:` and
:rst:`:searchKeywords:`. The options of a site set's settings are not
checked: the theme writes them itself.

A field that differs from an allowed one only in case, spaces, hyphens or
underscores gets the spelling that was probably meant:

..  code-block:: text

    The confval "cols" has the field "Default", which the manual does not
    declare. Did you mean the built-in "default"?

A rule for all manuals would not help: their fields differ, and each manual
knows its own.
