..  _check-headline-anchors:

======================
Check headline anchors
======================

..  toctree::

    Untitled
    Unchecked

..  _with-an-anchor:

With an anchor
==============

A headline with a label before it does not warn.

..  _anchor-before-an-index:

..  index:: Anchors

An anchor before an index entry
===============================

Neither does one whose label stands before an index entry.

Without an anchor
=================

This headline warns.

..  _nested-with-an-anchor:

Nested with an anchor
---------------------

A labelled headline inside an unlabelled one does not warn.

Nested without an anchor
------------------------

This one warns too.
