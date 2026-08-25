=========================================
Changelog links without a shortcode
=========================================

This manual sets no ``interlink-shortcode``.

.. _local-changelog-target:

Local changelog target
======================

..  versionchanged:: 2.1
    :changelog: #local-changelog-target

    The "#anchor" form is documented as requiring ``interlink-shortcode``. It is
    not set here, so a warning is logged and no link is rendered, even though the
    label it points at exists.

..  versionchanged:: 14.0
    :changelog: feature-107628-1729026000

    The core form does not depend on the setting and still renders its link.
