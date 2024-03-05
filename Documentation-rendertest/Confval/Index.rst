.. include:: /Includes.rst.txt
.. index:: ! confval
.. _confval:

=========================
confval
=========================

.. contents:: This page
   :backlinks: top
   :class: compact-list
   :depth: 99
   :local:


Summary
=======

`.. confval::` is the directive.

`:confval:` is a text role to create a reference to the description.


Examples:

*  A page where sphinx-doc.org uses the `:confval` directive:
   https://www.sphinx-doc.org/en/master/usage/configuration.html
   \• `rst-source
   <http://www.sphinx-doc.org/en/master/_sources/usage/configuration.rst.txt>`__

*  See long list of automatic entries for "configuration value" in the index:
   https://www.sphinx-doc.org/en/master/genindex.html#C


Demo 1
======

Source:

.. code-block:: rst

   .. confval:: mr_pommeroy

      :default: Happy new year, Sophie!
      :type:    shy

      Participant of Miss Sophie's birthday party.

Result:

.. confval:: mr_pommeroy

   :default: Happy new year, Sophie!
   :type:    shy

   Participant of Miss Sophie's birthday party.

You can easily link to the description of a 'confval' by means of the
`:confval:` text role. Example: Here is a link to :confval:`mr_pommeroy`.



Demo 2
======

.. highlight:: typoscript

Adapted from the TypoScript Reference Manual:

.. confval:: align

   :type:    align
   :default: left
   :Possible: \left | \center \| right

   Decides about alignment.

   Example::

      10.align = right



.. confval:: boolean

   :type: boolean
   :Possible: 1 | 0

   1 means TRUE and 0 means FALSE.

   Everything else is `evaluated to one of these values by PHP`__:
   Non-empty strings (except `0` [zero]) are treated as TRUE,
   empty strings are evaluated to FALSE.

   __ http://php.net/manual/en/language.types.boolean.php

   Examples::

      dummy.enable = 0   # false, preferred notation
      dummy.enable = 1   # true,  preferred notation
      dummy.enable =     # false, because the value is empty



.. confval:: case

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


.. _Demo 3 - addRecord:

Demo 3 - addRecord
==================

.. confval:: addRecord

   :type: array
   :Scope: fieldControl
   :Types: :ref:`group <Demo 3 - addRecord>`

   Control button to directly add a related record. Leaves the current view and opens a new form to add
   a new record. On 'Save and close', the record is directly selected as referenced element
   in the `type='group'` field. If multiple tables are :ref:`allowed <Demo 3 - addRecord>`, the
   first table from the allowed list is selected, if no specific `table` option is given.

   .. note::

      The add record control is disabled by default, enable it if needed. It
      is shown below the `edit popup` control if not changed by `below` or
      `after` settings.

