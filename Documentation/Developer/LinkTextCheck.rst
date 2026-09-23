..  include:: /Includes.rst.txt

..  _LinkTextCheck:

===================
Checking link texts
===================

A reference written without a link text of its own shows the title of its
target. The documentation guidelines ask for a text of its own instead, see
`Link text <https://docs.typo3.org/permalink/h2document:link-text>`__. A
manual can have the rendering warn about every reference that has none:

..  code-block:: xml
    :caption: Documentation/guides.xml

    <extension class="\T3Docs\Typo3DocsTheme\DependencyInjection\Typo3DocsThemeExtension"
               interlink-shortcode="my-manual"
               check-link-text="true"
    />

The check is off by default. Most manuals still have references without a link
text, and a warning fails a render with :bash:`--minimal-test`, which is what
their pipelines run.

What is checked
===============

..  rst-class:: dl-parameters

:rst:`:ref:` and :rst:`:doc:`
    Warns about :rst:`:ref:`my-label`` and :rst:`:doc:`Some/Page``, but not
    about :rst:`:ref:`Link text <my-label>``.

Permalinks
    Warns about a permalink URL written on its own, such as
    :samp:`https://docs.typo3.org/permalink/my-manual:my-label`, since it too
    shows the title of its target.

Not checked are the roles that show the name they point to, such as
:rst:`:php:`, :rst:`:confval:` or :rst:`:t3ext:`, and the :rst:`:changelog:`
option of :rst:`versionchanged` and its kind, which shows the title of the
changelog entry by design.

The warning names the file, but not the line, since a reference does not
record where it was written.

Switching the check per page
============================

A page can override the manual's setting with a field at its top, before the
title. This switches the check off for a page that shows a reference without
a link text on purpose:

..  code-block:: rst
    :caption: Documentation/Menus/NavigationTitle.rst

    :check-link-text: off

    ================
    Navigation title
    ================

:rst:`:check-link-text: on` works the other way, for a manual that is being
cleaned up page by page while the manual's setting is still off.
