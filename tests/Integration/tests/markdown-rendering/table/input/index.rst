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

A header row narrower than the body is widened: GFM ignores a body cell the
header has no column for, so the table would lose "one" without it.

..  t3-field-list-table::
    :header-rows: 1

    -   :Header1:   Only one header

    -   :Header1:   1
        :Header2:   one

GFM knows one header row, so a table with two of them writes the second as a
body row rather than dropping it.

..  table::

    +-----------+-----------+
    | Head A1   | Head B1   |
    +-----------+-----------+
    | Head A2   | Head B2   |
    +===========+===========+
    | 1         | one       |
    +-----------+-----------+

A list item that is not a field list is reported by name.

..  t3-field-list-table::

    -   Just a paragraph, not a field list

    -   :Header1:   1

A directive whose content is missing -- the sections below it were never
indented into it -- leaves a table with no cell at all. GFM cannot spell one,
so nothing is written.

..  t3-field-list-table::
    :header-rows: 1

The text after it is still part of the document.
