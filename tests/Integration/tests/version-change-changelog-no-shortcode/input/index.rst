=========================================
Changelog links without a shortcode
=========================================

This manual sets no ``interlink-shortcode``.

.. _local-changelog-target:

Local changelog target
======================

..  versionchanged:: 2.1
    :changelog: #local-changelog-target

    The "#anchor" form resolves against this manual's own labels and does not
    depend on ``interlink-shortcode``, which is unset here.

..  versionchanged:: 14.0
    :changelog: feature-107628-1729026000

    The core form does not depend on the setting and still renders its link.

..  versionchanged:: 12.4

    Without the option nothing is rendered beside the label and, because this
    manual expects no warning log at all, nothing may be logged either.
