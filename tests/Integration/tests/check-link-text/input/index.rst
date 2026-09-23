===============
Check link text
===============

..  toctree::

    Target
    Unchecked

..  _check-link-text-label:

Without a link text of their own
================================

Each of these warns: a :ref:`check-link-text-label`, a :doc:`Target`, and a
permalink https://docs.typo3.org/permalink/checklinktext:check-link-text-permalink
written without text.

..  _check-link-text-permalink:

A target for the permalink
==========================

With a link text of their own
=============================

None of these warns: a :ref:`labelled reference <check-link-text-label>`, a
:doc:`labelled document <Target>`, and a
`labelled permalink <https://docs.typo3.org/permalink/checklinktext:check-link-text-label>`__.

Showing the name they point to
==============================

None of these warns either, since they show the name they point to:
:php:`\TYPO3\CMS\Core\Utility\GeneralUtility` and :confval:`check-link-text-option`.

..  confval:: check-link-text-option
    :name: check-link-text-option

    An option to point to.

..  versionchanged:: 2.1
    :changelog: #check-link-text-label

    The changelog option shows the entry's title by design and does not warn.
