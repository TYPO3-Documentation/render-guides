=========================
Directives in table cells
=========================

..  list-table::
    :header-rows: 1

    *   -   Content
        -   Description
    *   -   ..  image:: /Images/icon.svg
                :alt: An image with options
        -   An image with options
    *   -   ..  image:: /Images/icon.svg
        -   An image without options
    *   -   Text before

            ..  image:: /Images/icon.svg
                :alt: An image after a paragraph
        -   An image after a paragraph
    *   -   ..  note::

                A note with an image

                ..  image:: /Images/icon.svg
                    :alt: An image in a note
        -   A note with an image

+-------------------+---------------+
| Content           | Description   |
+===================+===============+
| ..  note::        | A note        |
|                   |               |
|     A note        |               |
+-------------------+---------------+

..  t3-field-list-table::
    :header-rows: 1

    -   :Content:       Content
        :Description:   Description

    -   :Content:
            ..  image:: /Images/icon.svg
                :alt: An image in a t3-field-list-table
        :Description:   An image

    -   :Content:
            ..  container:: my-class

                A container
        :Description:   A container

..  csv-table::
    :header: "Content", "Description"

    "..  image:: /Images/icon.svg
        :alt: An image in a csv-table", "An image"
