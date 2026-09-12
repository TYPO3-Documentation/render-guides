# Configuration values in md

A confval is written as a list item so that the definition has an end:
everything indented under it belongs to it, and the next one starts where
the indentation does.

## Nested values

-   **mail**

    -   *Type:* array

    Settings for outgoing mail.

    -   **mail.transport**

        -   *Type:* string
        -   *Required:* true
        -   *Default:* sendmail

        How messages are handed over.

        -   **mail.transport.timeout**

            -   *Type:* int
            -   *Default:* 30

            Seconds before the transport gives up.

-   **cache**

    -   *Type:* array

    Settings for the cache.

## Without properties

-   **bare**

    A value with no type, no default and not required lists no properties
    at all, rather than saying "N/A" three times.

## Description with its own blocks

-   **complex**

    -   *Type:* string

    The description can hold more than a paragraph:

    -   a list item
    -   another one

    ```php
    $config['complex'] = 'value';
    ```

    And a closing paragraph, still inside the definition.
