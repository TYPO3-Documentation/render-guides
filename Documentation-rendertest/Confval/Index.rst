..  include:: /Includes.rst.txt
..  index:: ! confval
..  _confval:

=======
confval
=======

..  toctree::
    :glob:

    *

..  confval-menu::
    :display: table
    :exclude-noindex: true
    :exclude: addRecord
    :type:
    :Default:
    :Possible:

Summary
=======

`..  confval::` is the directive.

`:confval:` is a text role to create a reference to the description.


See also https://sphinx-toolbox.readthedocs.io/en/stable/extensions/confval.html#directive-option-confval-noindex


Demo 1
======

Source:

..  code-block:: rst

    ..  confval:: mr_pommeroy
        :Default: Happy new year, Sophie!
        :type:     shy

        Participant of Miss Sophie's birthday party.

Result:

..  confval:: mr_pommeroy
    :type: shy
    :Default: Happy new year, Sophie!

    Participant of Miss Sophie's birthday party.

You can easily link to the description of a 'confval' by means of the
`:confval:` text role. Example: Here is a link to :confval:`mr_pommeroy`.



Demo 2
======

..  highlight:: typoscript

Adapted from the TypoScript Reference Manual:

..  confval:: align
    :type:     align
    :Default: left
    :Possible: \left | \center \| right

    Decides about alignment.

    Example::

        10.align = right



    ..  confval:: boolean
        :type: boolean
        :Possible: 1 | 0

        1 means TRUE and 0 means FALSE.

        Everything else is evaluated to one of these values by PHP:
        Non-empty strings (except `0` [zero]) are treated as TRUE,
        empty strings are evaluated to FALSE.

        Examples::

            dummy.enable = 0    # false, preferred notation
            dummy.enable = 1    # true,  preferred notation
            dummy.enable =      # false, because the value is empty

        ..  confval:: boolean2
            :type: boolean
            :Possible: 1 | 0

            1 means TRUE and 0 means FALSE.

            Everything else is evaluated to one of these values by PHP:
            Non-empty strings (except `0` [zero]) are treated as TRUE,
            empty strings are evaluated to FALSE.

            Examples::

                dummy.enable = 0    # false, preferred notation
                dummy.enable = 1    # true,  preferred notation
                dummy.enable =      #



    ..  confval:: case
        :type: case

        :Possible:
            ===================== ==========================================================
            Value                 Effect
            ===================== ==========================================================
            :ts:`upper`           Convert all letters of the string to upper case
            :ts:`lower`           Convert all letters of the string to lower case
            :ts:`capitalize`      Uppercase the first character of each word in the string
            :ts:`ucfirst`         Convert the first letter of the string to upper case
            :ts:`lcfirst`         Convert the first letter of the string to lower case
            :ts:`uppercamelcase`  Convert underscored `upper_camel_case` to `UpperCamelCase`
            :ts:`lowercamelcase`  Convert underscored `lower_camel_case` to `lowerCamelCase`
            ===================== ==========================================================

        Do a case conversion.

        Example code::

            10 = TEXT
            10.value = Hello world!
            10.case = upper

        Result::

            HELLO WORLD!


..  _Demo 3 - addRecord:

Demo 3 - addRecord
==================

..  confval:: addRecord
    :type: array
    :Scope: fieldControl
    :Types: :ref:`group <Demo 3 - addRecord>`

    Control button to directly add a related record. Leaves the current view and opens a new form to add
    a new record. On 'Save and close', the record is directly selected as referenced element
    in the `type='group'` field. If multiple tables are :ref:`allowed <Demo 3 - addRecord>`, the
    first table from the allowed list is selected, if no specific `table` option is given.

    ..  note::

        The add record control is disabled by default, enable it if needed. It
        is shown below the `edit popup` control if not changed by `below` or
        `after` settings.


Confval with name
=================

..  confval:: addRecord
    :name: another-context-addRecord
    :type: array
    :Scope: fieldControl
    :Types: :ref:`group <Demo 3 - addRecord>`

    Lorem Ipsum

Link here with :confval:`another-context-addRecord`, link to the one above with
:confval:`addRecord`.

..  _confval-with-noindex:

Confval with noindex
====================

..  confval:: addRecord
    :noindex:
    :type: array
    :Scope: fieldControl
    :Types: :ref:`group <Demo 3 - addRecord>`

    Lorem Ipsum

You cannot link here with the `:confval:` textrole, but only with `:ref:` to the
reference above it. :ref:`confval-with-noindex`.
