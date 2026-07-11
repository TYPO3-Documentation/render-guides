==============================
Version changes with changelog
==============================

..  versionchanged:: 14.0
    :changelog: feature-107628-1729026000

    Most modules have been moved from :guilabel:`System` to
    :guilabel:`Administration`. This core entry resolves to a real URL.

..  versionadded:: 14.0
    :changelog: feature-101010-1700000000

    A non-existent core entry: a warning is logged and no link is rendered.

..  deprecated:: 14.0
    :changelog: deprecation-202020-1700000000

    Another non-existent core entry: warning, no link.

..  versionchanged:: 2.0
    :changelog: other-vendor/other-ext:changes-2-0-0

    Extension form "<vendor>/<package>:<anchor>". The external inventory is
    not available here, so this warns and renders no link.

.. _local-changelog-target:

Local changelog target
======================

..  versionchanged:: 2.1
    :changelog: #local-changelog-target

    Local form: "#anchor" resolves against this manual's own labels.

..  versionchanged:: 2.2
    :changelog: acme/acme-blog:local-changelog-target

    Explicit reference to the manual's own interlink-shortcode
    ("acme/acme-blog") resolves as a local reference, like the "#" form.

..  versionchanged:: 12.4

    Without the changelog option the block renders exactly as before.
