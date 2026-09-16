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
    :changelog: featureã€€107628

    An ideographic space: whitespace the ASCII character class does not see, which
    would otherwise travel into the reference as an invisible, unresolvable target.

..  versionchanged:: 2.8
    :changelog: other+vendor:changes-2-8-0

    A shortcode character an interlink domain cannot carry, with no whitespace
    to reject it first: only the canonical parser refuses this, a hand-rolled
    split on ":" would accept "other+vendor" as the domain.

..  versionchanged:: 3.1
    :changelog: featÃure-1

    Maintainer note: the value above carries a deliberate lone 0xC3 byte, so
    this file is NOT valid UTF-8 and an editor that re-encodes it will turn the
    byte into U+FFFD and break this case. It is kept as a fixture anyway because
    without the guard this input aborts the whole render - no pages at all, and
    an error naming the Twig template rather than this file.

..  versionchanged:: 4.0
    :changelog: How the teaser field was renamed <feature-107628-1729026000>

    The embedded form: the text before the brackets becomes the link text, so an
    author can say what the entry is where its own title does not.

..  versionchanged:: 4.1
    :changelog: The local changelog <#local-changelog-target>

    The embedded form over the local "#anchor" form.

..  versionchanged:: 4.2
    :changelog: <feature-107628-1729026000>

    Brackets with nothing before them: no text is supplied, so the link reads as
    the entry's own title, like the bare form.

..  versionchanged:: 4.3
    :changelog: Two words <feature 107628>

    Whitespace inside the brackets: the entry is still a single token, and the
    text being allowed to carry spaces does not extend to the entry.

..  versionchanged:: 4.4
    :changelog: A text and nothing else <>

    Empty brackets: the parser does not read this as an embedded reference, so
    the whole value stands as the entry and is rejected for its whitespace.

..  versionchanged:: 12.4

    Without the changelog option the block renders exactly as before.
