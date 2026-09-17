=========================================
Changelog links without a shortcode
=========================================

This manual sets no ``interlink-shortcode``.

..  Maintainer note: this fixture ships no ``expected/logs`` directory, and that
    absence is load-bearing - IntegrationTest asserts no warning log was written
    at all. It is the only assertion in the suite that a stray warning cannot
    slip past, because the expected logs elsewhere are compared by line
    containment. Keep this manual free of anything that fetches an inventory,
    and do not give it an expected log.

.. _local-changelog-target:

Local changelog target
======================

..  versionchanged:: 2.1
    :changelog: #local-changelog-target

    The "#anchor" form resolves against this manual's own labels and does not
    depend on ``interlink-shortcode``, which is unset here.

..  versionchanged:: 2.2
    :changelog: Where the option was renamed <#local-changelog-target>

    The embedded form over the same local target: the author's text replaces the
    title the reference would otherwise be filled with. Resolved offline, so this
    case cannot be tripped by a failed inventory fetch either.

..  versionchanged:: 12.4

    Without the option nothing is rendered beside the label and, because this
    manual expects no warning log at all, nothing may be logged either.
