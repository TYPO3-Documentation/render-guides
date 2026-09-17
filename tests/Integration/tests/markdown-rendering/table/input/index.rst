======
Tables
======

A table with cells becomes a GFM pipe table.

..  t3-field-list-table::
    :header-rows: 1

    -   :Header1:   Header1
        :Header2:   Header2

    -   :Header1:   1
        :Header2:   one

A grid table keeps its header row too.

..  table::

    +-----------+-----------+
    | Header1   | Header2   |
    +===========+===========+
    | 1         | one       |
    +-----------+-----------+

A directive whose content is missing -- the sections below it were never
indented into it -- leaves a table with no cell at all. GFM cannot spell one,
so nothing is written.

..  t3-field-list-table::
    :header-rows: 1

The text after it is still part of the document.
