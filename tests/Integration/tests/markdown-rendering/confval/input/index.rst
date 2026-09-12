==========================
Configuration values in md
==========================

A confval is written as a list item so that the definition has an end:
everything indented under it belongs to it, and the next one starts where
the indentation does.

Nested values
=============

..  confval-menu::
    :name: mail
    :display: tree
    :type:
    :default:

    ..  confval:: mail
        :name: mail
        :type: array

        Settings for outgoing mail.

        ..  confval:: mail.transport
            :name: mail-transport
            :type: string
            :default: sendmail
            :required: true

            How messages are handed over.

            ..  confval:: mail.transport.timeout
                :name: mail-transport-timeout
                :type: int
                :default: 30

                Seconds before the transport gives up.

    ..  confval:: cache
        :name: cache
        :type: array

        Settings for the cache.

Without properties
==================

..  confval:: bare
    :name: bare

    A value with no type, no default and not required lists no properties
    at all, rather than saying "N/A" three times.

Description with its own blocks
===============================

..  confval:: complex
    :name: complex
    :type: string

    The description can hold more than a paragraph:

    -   a list item
    -   another one

    ..  code-block:: php

        $config['complex'] = 'value';

    And a closing paragraph, still inside the definition.
