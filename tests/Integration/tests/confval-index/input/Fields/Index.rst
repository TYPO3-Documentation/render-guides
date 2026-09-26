..  _fields:

======
Fields
======

..  _fields-input:

Input fields
============

..  confval:: size
    :name: input-size
    :type: integer
    :default: 30
    :Path: $GLOBALS['TCA'][$table]['columns'][$field]['config']
    :Scope: Display

    Abstract value for the width of the field, see :ref:`fields`.

    A second paragraph is not part of the summary.

..  confval:: behaviour
    :name: input-behaviour
    :type: array

    ..  confval:: allowLanguageSynchronization
        :name: input-behaviour-allowlanguagesynchronization
        :type: boolean
        :required:

        Allows an editor to select in a localized record whether the value is
        copied over from the default language.

..  confval:: hidden
    :name: input-hidden
    :noindex:

    Documented here, but not listed.
