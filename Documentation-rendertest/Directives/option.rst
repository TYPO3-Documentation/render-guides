..  include:: /Includes.rst.txt
..  _option-directive:

======
option
======

`..  option::` documents a single command line option or configuration key and
creates a link target for it.

The argument may list several spellings separated by commas — each one gets its
own anchor, in addition to one anchor built from the whole argument.

Angle brackets mark a placeholder, the conventional way to write a value the
reader has to supply: `--site <identifier>` means `--site` followed by an actual
site identifier. Placeholders are removed from the per-spelling anchors, but
only there — the anchor built from the whole argument keeps them.

Not to be confused with :ref:`console_commands`, which renders a whole command
including its options from a JSON file.


Demo 1 - a configuration key with a field list
==============================================

Source:

..  code-block:: rst

    ..  option:: typo3.version

        :type: string
        :Example: `14.3.0`

        The current TYPO3 version.

Result:

..  option:: typo3.version

    :type: string
    :Example: `14.3.0`

    The current TYPO3 version.


Demo 2 - multiple spellings and a placeholder
=============================================

A comma-separated argument documents the long and short form together.

Source:

..  code-block:: rst

    ..  option:: --verbose, -v

        Increase the verbosity of messages.

    ..  option:: --site <identifier>, -s <identifier>

        Limit the command to one site.

Result:

..  option:: --verbose, -v

    Increase the verbosity of messages.

..  option:: --site <identifier>, -s <identifier>

    Limit the command to one site.

The first one is reachable as `verbose`, `v` and `verbose-v`. The second one
shows the placeholder handling: `site` and `s` have the `<identifier>` removed,
while the whole-argument anchor keeps it as `site-identifier-s-identifier`.
