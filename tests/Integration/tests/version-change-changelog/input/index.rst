==============================
Version changes with changelog
==============================

..  versionchanged:: 14.0
    :changelog: feature-107628-1729026000

    Most modules have been moved from :guilabel:`System` to
    :guilabel:`Administration`. This core entry resolves to a real URL.

..  versionadded:: 14.0
    :changelog: feature-101010-1700000000

    A non-existent core entry: a warning is logged and the unresolved marker is
    rendered instead of a link.

..  deprecated:: 14.0
    :changelog: deprecation-202020-1700000000

    Another non-existent core entry: warning, unresolved marker.

..  versionchanged:: 2.0
    :changelog: other-vendor/other-ext:changes-2-0-0

    Extension form "<vendor>/<package>:<anchor>". The external inventory is
    not available here, so this warns and renders the unresolved marker.

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

..  versionchanged:: 2.7
    :changelog: #no-such-local-label

    The local form is validated: a "#anchor" nothing declares warns and is marked
    unresolved, rather than passing through as a silent dead fragment link.

..  versionchanged:: 2.3
    :changelog: :changes-2-3-0

    Malformed: nothing usable before the colon. A warning is logged and no link
    is rendered, rather than a reference to a label named ":changes-2-3-0".

..  versionchanged:: 2.4
    :changelog: other vendor:changes-2-4-0

    A space inside the value: rejected as whitespace before the shortcode is
    even looked at, rather than taken for a shortcode named "other vendor".

..  versionchanged:: 2.5
    :changelog: other-vendor/other-ext:

    A shortcode with nothing after the colon: the anchor is empty, so a warning
    is logged and no link is rendered.

..  versionchanged:: 2.6
    :changelog: #

    The local form with an empty anchor, warned about the same way.

..  versionadded:: 1.0
    :changelog:

    The option without a value: warned about, and not taken for the changelog
    entry id "1" that the flag would otherwise stringify to.

..  versionchanged:: 14.0
    :changelog:
        feature-107628-1729026000

    The value written on the following line. The parser appends it to the flag
    the valueless option produced, so the value arrives as "1 feature-...";
    it is rejected as a value that is not a single token, rather than resolved as
    one. The value is therefore read before it is trimmed: written with a trailing
    space after ":changelog:" the same shape arrives with a leading space instead
    of the "1", and that shape cannot be a case here because this repository's
    .editorconfig strips trailing whitespace from .rst files.

..  versionchanged:: 3.0
    :changelog: feature　107628

    An ideographic space: whitespace the ASCII character class does not see, which
    would otherwise travel into the reference as an invisible, unresolvable target.

..  versionchanged:: 2.8
    :changelog: other+vendor:changes-2-8-0

    A shortcode character an interlink domain cannot carry, with no whitespace
    to reject it first: only the canonical parser refuses this, a hand-rolled
    split on ":" would accept "other+vendor" as the domain.

..  versionchanged:: 12.4

    Without the changelog option the block renders exactly as before.
