..  include:: /Includes.rst.txt

..  _HeadlineAnchorCheck:

=========================
Checking headline anchors
=========================

The documentation guidelines ask for an anchor on every headline, so that a
permalink can lead to it, see
`Link anchors <https://docs.typo3.org/permalink/h2document:link-anchor>`__.
Only a label written before a headline registers such an anchor. The id the
rendering derives from the title works on the page alone, and changes when
the headline is reworded.

A manual can have the rendering warn about every headline without one:

..  code-block:: xml
    :caption: Documentation/guides.xml

    <extension class="\T3Docs\Typo3DocsTheme\DependencyInjection\Typo3DocsThemeExtension"
               interlink-shortcode="my-manual"
               check-headline-anchors="true"
    />

The warning names the headline and suggests a label made from it:

..  code-block:: text

    The headline "Inline columns" has no anchor, so no permalink leads to it.
    Give it one: ..  _inline-columns:

The check is off by default. Many manuals, and those of third-party
extensions most of all, still have headlines without a label, and a warning
fails a render with :bash:`--minimal-test`, which is what their pipelines run.

What is checked
===============

Every headline, the page title included. A label counts when it stands before
the headline, with only blank lines or an :rst:`..  index::` directive between
them:

..  code-block:: rst

    ..  _inline-columns:

    Inline columns
    ==============

Whether an anchor is used twice is not part of this check: the rendering
already warns about a duplicate anchor on its own.

A manual written in Markdown is never checked, even with the setting on. A
Markdown headline cannot carry a label, so nothing could fix the warning.

Switching the check per page
============================

A page can override the manual's setting with a field at its top, before the
title:

..  code-block:: rst

    :check-headline-anchors: off

    =====
    Title
    =====

:rst:`:check-headline-anchors: on` works the other way, for a manual that is
being given its anchors page by page while the manual's setting is still off.

The check works the same way as the one for
:ref:`link texts <LinkTextCheck>`, and a manual can switch on either or both.
