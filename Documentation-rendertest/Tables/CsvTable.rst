..  include:: /Includes.rst.txt
..  _tables-csv:

=========
csv-table
=========

`..  csv-table::` builds a table from comma separated values, either written
inline in the directive body or read from an external file with `:file:`.

Use `:caption:` for the table caption. The directive also accepts an argument,
but it is silently ignored — `CsvTableDirective` never reads it.

See :ref:`tables-directive` for the `table` directive and :ref:`Tables` for
grid, simple and `t3-field-list-table` tables.


Demo 1 - inline CSV with a header
=================================

Source:

..  code-block:: rst

    ..  csv-table::
        :caption: Numbers
        :header: "Header 1", "Header 2"
        :widths: 15, 15

        1, "one"
        2, "two"

Result:

..  csv-table::
    :caption: Numbers
    :header: "Header 1", "Header 2"
    :widths: 15, 15

    1, "one"
    2, "two"


Demo 2 - header taken from the data
===================================

`:header-rows:` promotes the first N rows of the data itself to header rows,
instead of declaring them separately with `:header:`.

Source:

..  code-block:: rst

    ..  csv-table::
        :caption: TYPO3 versions
        :header-rows: 1

        "Version", "Type", "Supported until"
        "13.4", "LTS", "April 2027"

Result:

..  csv-table::
    :caption: TYPO3 versions
    :header-rows: 1

    "Version", "Type", "Supported until"
    "14.3", "Sprint release", "next sprint release"
    "13.4", "LTS", "April 2027"
    "12.4", "ELTS", "March 2028"


Demo 3 - values containing commas
=================================

A value that itself contains a comma must be quoted, and a literal double quote
inside it is written by doubling it.

..  csv-table::
    :caption: Quoting
    :header: "Input", "Renders as"
    :widths: 40, 60

    "``""x, y""``", "x, y"
    "``""She said """"no""""""``", "She said ""no"""


Demo 4 - no caption, no header
==============================

All options are optional.

..  csv-table::

    1, 2, 3
    4, 5, 6
