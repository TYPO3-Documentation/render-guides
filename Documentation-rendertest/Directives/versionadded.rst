
.. include:: /Includes.rst.txt


=========================
versionadded & friends
=========================

Read about the `versionadded directive`__ in the `Sphinx docs`__.

__ https://www.sphinx-doc.org/en/master/usage/restructuredtext/directives.html#directive-versionadded
__ https://www.sphinx-doc.org/en/master/

Examples
========

versionadded
   .. versionadded:: 4.5
      The *spam* parameter
   .. versionadded:: 3.1
   .. versionadded:: 2.5
      The *spam* parameter
   .. versionadded:: 2.1

versionchanged
   .. versionchanged:: 8.7

   .. versionchanged:: 6.0
      Namespaces everywhere


deprecated
   .. deprecated:: 3.1
      Use function `spam` instead.

   .. deprecated:: 2.7

The following seealso should be re-styled to a more reduced visual appearance:

.. seealso::

   Something of interest
      Visit https://typo3.org first.

   There's a company as well
      TYPO3 — the Professional, Flexible Content Management Solution

      https://typo3.com


There’s also a “short form” allowed that looks like this:

.. seealso:: https://typo3.org, https://typo3.com

Linking to a changelog entry
============================

..  Maintainer note: the live example below must use a REAL, published core
    changelog entry, otherwise it warns and fails the "renders without warning"
    gate. The extension and "#anchor" forms are shown as ``code-block`` (not live
    directives) on purpose: their fictional targets cannot resolve here and would
    trip the same gate.

The ``:changelog:`` option adds a link to the related changelog entry. The
value is resolved as a cross-reference — a bare identifier against the core
changelog inventory, ``vendor/package:anchor`` against that manual's inventory,
``#anchor`` against this manual's own labels — so a target that does not exist
produces a build warning and is marked unresolved, like any other unresolvable
cross-reference, instead of becoming a dead link.

For a TYPO3 core change, pass the changelog entry identifier:

..  code-block:: rst

    ..  versionchanged:: 14.0
        :changelog: feature-107628-1729026000

        Most modules have been moved from :guilabel:`System` to
        :guilabel:`Administration`.

which renders as:

..  versionchanged:: 14.0
    :changelog: feature-107628-1729026000

    Most modules have been moved from :guilabel:`System` to
    :guilabel:`Administration`.

For an extension change, pass the extension's interlink shortcode
(``vendor/package``) plus the changelog entry anchor:

..  code-block:: rst

    ..  versionchanged:: 2.0
        :changelog: acme/acme-blog:changes-2-0-0

        The teaser field was renamed; see the changelog entry for the migration.

When linking the changelog of the current manual itself, use the short
``#anchor`` form. It resolves against this manual's own labels:

..  code-block:: rst

    ..  versionchanged:: 2.1
        :changelog: #changes-2-1-0

        A local changelog reference, without repeating the shortcode.

The link text is the title of the entry the reference resolves to, so each link
says which change it leads to. Where that title does not describe the change —
an extension whose whole changelog carries a single label, for instance — give
the text explicitly, in the embedded form every other reference uses:

..  code-block:: rst

    ..  versionchanged:: 2.0
        :changelog: Renaming the teaser field <acme/acme-blog:changelog>

        The teaser field was renamed; see the changelog entry for the migration.

which renders, against a real core entry, as:

..  versionchanged:: 14.0
    :changelog: How backend modules were renamed <feature-107628-1729026000>

    The same entry as above, linked under a text of the author's choosing.
