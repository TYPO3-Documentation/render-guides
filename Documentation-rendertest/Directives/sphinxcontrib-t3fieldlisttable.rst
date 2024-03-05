.. include:: /Includes.rst.txt

.. _sphinxcontrib-t3fieldlisttable:

==============================
sphinxcontrib-t3fieldlisttable
==============================

See https://github.com/TYPO3-Documentation/sphinxcontrib.t3fieldlisttable/tree/develop

.. contents:: This page
   :backlinks: top
   :class: compact-list
   :depth: 99
   :local:


Learning by example: The "t3-field-list-table" directive
========================================================

The most simple form
--------------------
The most simple form consists of just the directive statement,
one bullet list item and one field list item.

Source
~~~~~~
::

   .. t3-field-list-table::

    * :a: Cell A

Result
~~~~~~
.. t3-field-list-table::

 * :a: Cell A



The most simple form nested in another field name table
-------------------------------------------------------

.. t3-field-list-table::
 :definition-row: yes
 :header-rows: 1

 * :left:
   :right:

 * :left..right:
      The most simple form consists of the directive,
      one bullet list item and one field list item.
      The source is in the left column and the output
      in the right column.

 * :left:
      Source::

        .. t3-field-list-table::

         * :a: Cell A

   :right:
      Result:

      .. t3-field-list-table::

       * :a: Cell A




A more illustrative example
---------------------------
The names of the field list like "year", "type a" and "type b" are identifiers of columns
and therefore called "columnId". The are used internally only.

**The first field list always** is the *definition row*. Following field lists are *data rows*.
In data rows the order of the items doesn't matter. For *data rows* columns may be omitted.

In this example we have a table title, one header row and one stub column. The "year" column is
given a width of 10%. The remaining 90% are equally split and given to the
remaining columns.

Source
~~~~~~
::

   .. t3-field-list-table:: Albums of Peter, Paul & Mary.
    :header-rows: 1
    :stub-columns: 1

    * :year,10:    Year
      :type a:  Album Type A
      :type b:  Album Type B

    * :type a:  Peter, Paul and Mary
      :year:    1962

    * :type a:  Moving
      :year:    1963

    * :year:    1963
      :type b:  In the Wind

    * :year:    1964
      :type a:  In Concert

    * :year:    1965
      :type b:  ASong will Rise

    * :year:    1965
      :type a:  See What Tomorrow Brings


Result
~~~~~~
.. t3-field-list-table:: Albums of Peter, Paul & Mary.
 :header-rows: 1
 :stub-columns: 1

 * :year,10:    Year
   :type a:  Album Type A
   :type b:  Album Type B

 * :type a:  Peter, Paul and Mary
   :year:    1962

 * :type a:  Moving
   :year:    1963

 * :year:    1963
   :type b:  In the Wind

 * :year:    1964
   :type a:  In Concert

 * :year:    1965
   :type b:  ASong will Rise

 * :year:    1965
   :type a:  See What Tomorrow Brings



The "More illustrative example" nested in another t3-field-list-table
---------------------------------------------------------------------

.. t3-field-list-table::
 :definition-row: yes
 :header-rows: 1

 * :1:
   :2:

 * :1..2:
        Example "Column identifiers" nested in another t3-field-list-table

 * :1..2:
        The names of the field list like "year", "type a" and "type b" are identifiers of columns
        and therefore called "columnId". The are used internally only.

        **The first field list always** is the *definition row*. Following field lists are *data rows*.
        In data rows the order of the items doesn't matter. For *data rows* columns may be omitted.

        In this example we have a table title, one header row and one stub column. The "year" column is
        given a width of 10%. The remaining 90% are equally split and given to the
        remaining columns.

 * :1:
     ::

        .. t3-field-list-table:: Albums of Peter, Paul & Mary.
         :header-rows: 1
         :stub-columns: 1

         * :year,10:    Year
           :type a:  Album Type A
           :type b:  Album Type B

         * :type a:  Peter, Paul and Mary
           :year:    1962

         * :type a:  Moving
           :year:    1963

         * :year:    1963
           :type b:  In the Wind

         * :year:    1964
           :type a:  In Concert

         * :year:    1965
           :type b:  ASong will Rise

         * :year:    1965
           :type a:  See What Tomorrow Brings


   :2:
     .. t3-field-list-table:: Albums of Peter, Paul & Mary.
      :header-rows: 1
      :stub-columns: 1

      * :year,10:    Year
        :type a:  Album Type A
        :type b:  Album Type B

      * :type a:  Peter, Paul and Mary
        :year:    1962

      * :type a:  Moving
        :year:    1963

      * :year:    1963
        :type b:  In the Wind

      * :year:    1964
        :type a:  In Concert

      * :year:    1965
        :type b:  ASong will Rise

      * :year:    1965
        :type a:  See What Tomorrow Brings



Column spans
------------

We can have column spans. To make these possible even for
the first table row there is an option "definition-row"
working as a flag. It defaults to "no".

If "yes", the flag indicates
that the first row is used to define the table columns only.
Its data is then discarded and stripped
from final output.

Source
~~~~~~
::

   .. t3-field-list-table:: Table with column spans.
    :definition-row: 1
    :header-rows: 2

    * :a: Ignored, because this is the definition row.
      :b: We need an explicit definition row, because the table
      :c: starts with a span.

    * :a..c: This header cell in the first row of the table
             spans the whole table row

    * :a: Column A
      :b: Column B
      :c: Column C

    * :a: one
      :b: two
      :c: three

    * :a: one
      :b..c: two, three

    * :a..b: one, two
      :c: three

    * :a..c: one, two, three


Result
~~~~~~

.. t3-field-list-table:: Table with column spans.
 :definition-row: 1
 :header-rows: 2

 * :a: Ignored, because this is the definition row.
   :b: We need an explicit definition row, because the table
   :c: starts with a span.

 * :a..c: This header cell in the first row of the table
          spans the whole table row

 * :a: Column A
   :b: Column B
   :c: Column C

 * :a: one
   :b: two
   :c: three

 * :a: one
   :b..c: two, three

 * :a..b: one, two
   :c: three

 * :a..c: one, two, three

Complex example for column and row spans
----------------------------------------

Source
~~~~~~
::

   .. t3-field-list-table:: Table with row and column spans
    :definition-row: 1
    :header-rows: 2

    * :a:
      :b:
      :c:
      :d:

    * :a:
      :b..c: Middle top
      :d:

    * :(a):
      :b..c: Middle bottom
      :(d):

    * :a: Column A
      :b: Column B
      :c: Column C
      :d: y

    * :a: one
      :b: two
      :c: three
      :(d):

    * :(a):
      :b..c: two, three
      :d: y

    * :a..b: one, two
      :c: three
      :(d):

    * :a..c: one, two, three
      :(d):


Result
~~~~~~

.. t3-field-list-table:: Table with row and column spans
 :definition-row: 1
 :header-rows: 2

 * :a:
   :b:
   :c:
   :d:

 * :a:
   :b..c: Middle top
   :d:

 * :(a):
   :b..c: Middle bottom
   :(d):

 * :a: Column A
   :b: Column B
   :c: Column C
   :d: y

 * :a: one
   :b: two
   :c: three
   :(d):

 * :(a):
   :b..c: two, three
   :d: y

 * :a..b: one, two
   :c: three
   :(d):

 * :a..c: one, two, three
   :(d):



Another complex spanning example
--------------------------------

Complex spanning pattern (no edge knows all rows and columns):

Source in grid notation
~~~~~~~~~~~~~~~~~~~~~~~
::

   +-----------+-------------------------+
   | W/NW cell | N/NE cell               |
   |           +-------------+-----------+
   |           | Middle cell | E/SE cell |
   +-----------+-------------+           |
   | S/SE cell               |           |
   +-------------------------+-----------+

Result of grid notation
~~~~~~~~~~~~~~~~~~~~~~~
+-----------+-------------------------+
| W/NW cell | N/NE cell               |
|           +-------------+-----------+
|           | Middle cell | E/SE cell |
+-----------+-------------+           |
| S/SE cell               |           |
+-------------------------+-----------+

Source in t3-field-list-table notation
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
::

    .. t3-field-list-table::
     :definition-row: yes

     * :a:
       :b:
       :c:

     * :a:    W/NW cell
       :b..c: N/NE cell

     * :(a):
       :b:    Middle cell
       :c:    E/SE cell

     * :a..b: S/SW cell
       :(c):


Result of t3-field-list-table notation
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
.. t3-field-list-table::
 :definition-row: yes

 * :a:
   :b:
   :c:

 * :a:    W/NW cell
   :b..c: N/NE cell

 * :(a):
   :b:    Middle cell
   :c:    E/SE cell

 * :a..b: S/SW cell
   :(c):



Comprehensive docutils example table
------------------------------------

Source given as standard grid notation (A)
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
::

   +------------------------+------------+----------+----------+
   | Header row, column 1   | Header 2   | Header 3 | Header 4 |
   | (header rows optional) |            |          |          |
   +========================+============+==========+==========+
   | body row 1, column 1   | column 2   | column 3 | column 4 |
   +------------------------+------------+----------+----------+
   | body row 2             | Cells may span columns.          |
   +------------------------+------------+---------------------+
   | body row 3             | Cells may  | - Table cells       |
   +------------------------+ span rows. | - contain           |
   | body row 4             |            | - body elements.    |
   +------------------------+------------+----------+----------+
   | body row 5             | Cells may also be     |          |
   |                        | empty: ``-->``        |          |
   +------------------------+-----------------------+----------+

Source given as t3-field-list-table notation (B)
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
::

    .. t3-field-list-table::
     :header-rows: 1

     * :a:      Header row, column 1 (header rows optional)
       :b:      Header 2
       :c:      Header 3
       :d:      Header 4

     * :a:      body row 1, column 1
       :b:      column 2
       :c:      column 3
       :d:      column 4

     * :a:      body row 2
       :b..d:   Cells may span columns.

     * :a:      body row 3
       :b:      Cells may span rows.
       :c..d:   - Table cells
                - contain
                - body elements.

     * :a:      body row 4
       :(b):
       :(c..d):

     * :a:      body row 5
       :b..c:   Cells may also be empty: ``-->``


Result of standard grid notation (A)
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

+------------------------+------------+----------+----------+
| Header row, column 1   | Header 2   | Header 3 | Header 4 |
| (header rows optional) |            |          |          |
+========================+============+==========+==========+
| body row 1, column 1   | column 2   | column 3 | column 4 |
+------------------------+------------+----------+----------+
| body row 2             | Cells may span columns.          |
+------------------------+------------+---------------------+
| body row 3             | Cells may  | - Table cells       |
+------------------------+ span rows. | - contain           |
| body row 4             |            | - body elements.    |
+------------------------+------------+----------+----------+
| body row 5             | Cells may also be     |          |
|                        | empty: ``-->``        |          |
+------------------------+-----------------------+----------+


Result of t3-field-list-table notation (B)
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
**Note:**
The structure of (B) **equals exactly** the structure of (A). For some
reasons the browser may choose different default widths
for the columns.

.. t3-field-list-table::
 :header-rows: 1

 * :a:      Header row, column 1 (header rows optional)
   :b:      Header 2
   :c:      Header 3
   :d:      Header 4

 * :a:      body row 1, column 1
   :b:      column 2
   :c:      column 3
   :d:      column 4

 * :a:      body row 2
   :b..d:   Cells may span columns.

 * :a:      body row 3
   :b:      Cells may span rows.
   :c..d:   - Table cells
            - contain
            - body elements.

 * :a:      body row 4
   :(b):
   :(c..d):

 * :a:      body row 5
   :b..c:   Cells may also be
            empty: ``-->``




Alignment for columns
---------------------

A default alignment can be specified for each column. The alignment
may be given by one word from ['left', 'right', 'justify', 'center']
and one word from ['top', 'middle', 'bottom']. Words are separated
by one ore more spaces.
The words may be shortend down to one letter. Letters may be upper or lower case.
The following spellings all mean the same:

| right top -> "right top"
| top   right right top top right -> "right top"
| RiGhT ToP  -> "right top"
| RI TO  -> "right top"
| R T  -> "right top"
| r t  -> "right top"

| Contradicting values lead to an error:
|
| left right top -> error
| Top Bottom -> error
| L R -> error
| B T -> error

The actual rendering depends on the writer. The modified HTML-writer used here adds classes 'left', 'right', 'center', 'justify', 'top', 'middle', 'bottom' to the TH ord TD tag of the table cell.

.. t3-field-list-table::
 :header-rows: 1

 * :----------------------------------:
   :0:
   :1,,l:         left
   :2,,c:         center
   :3,,r:         right
   :4,,j:         justify
   :5,,l t:       left top
   :6,,c t:       center top
   :7,,r t:       right top
   :8,,j t:       justify top

 * :----------------------------------:
   :0:            | a
                  | b
                  | c
   :1:            a b c
   :2:            a b c
   :3:            a b c
   :4:            a b c
   :5:            a b c
   :6:            a b c
   :7:            a b c
   :8:            a b c

 * :----------------------------------:
   :0:            | b
                  | c
                  | d
   :1:            b c d
   :2:            b c d
   :3:            b c d
   :4:            b c d
   :5:            b c d
   :6:            b c d
   :7:            b c d
   :8:            b c d

 * :----------------------------------:
   :0:            | c
                  | d
                  | e
   :1:            c d e
   :2:            c d e
   :3:            c d e
   :4:            c d e
   :5:            c d e
   :6:            c d e
   :7:            c d e
   :8:            c d e


.. t3-field-list-table::
 :header-rows: 1

 * :----------------------------------:
   :0:
   :1,,l m:       left middle
   :2,,c m:       center middle
   :3,,r m:       right middle
   :4,,j m:       justify middle
   :5,,l b:       left bottom
   :6,,c b:       center bottom
   :7,,r b:       right tbottom
   :8,,j b:       justify bottom

 * :----------------------------------:
   :0:            | a
                  | b
                  | c
   :1:            a b c
   :2:            a b c
   :3:            a b c
   :4:            a b c
   :5:            a b c
   :6:            a b c
   :7:            a b c
   :8:            a b c

 * :----------------------------------:
   :0:            | b
                  | c
                  | d
   :1:            b c d
   :2:            b c d
   :3:            b c d
   :4:            b c d
   :5:            b c d
   :6:            b c d
   :7:            b c d
   :8:            b c d

 * :----------------------------------:
   :0:            | c
                  | d
                  | e
   :1:            c d e
   :2:            c d e
   :3:            c d e
   :4:            c d e
   :5:            c d e
   :6:            c d e
   :7:            c d e
   :8:            c d e



Alignment for individual table cells
------------------------------------

Each cell may be given an individual alignment.


.. t3-field-list-table::
 :header-rows: 1
 :stub-columns: 1

 * :----------------------------------:
   :0,,:
   :1,,l:         left
   :2,,c:         center
   :3,,r:         right
   :4,,j:         justify
   :5,,:          dummy

 * :0,,t:           top
   :1,,t:        a b c
   :2,,t:        a b c
   :3,,t:        a b c
   :4,,t:        a b c
   :5,,c:        | a
                 | b
                 | c

 * :0:           middle
   :1,,m:        B c d
   :2,,m:        B c d
   :3,,m:        B c d
   :4,,m:        B c d
   :5,,c:        | B
                 | c
                 | d

 * :0:           bottom
   :1,,b:        C d e
   :2,,b:        C d e
   :3,,b:        C d e
   :4,,b:        C d e
   :5,,c:        | C
                 | d
                 | e

 * :0:           individual
   :1,,r t:      right top
   :2,,l b:      left bottom
   :3,,c m:      center middle
   :4,,:         default
   :5,,c:        | C
                 | d
                 | e





Defining columns: column identifiers
------------------------------------
The first row is the *definition row*. The order of the field list items
determine the order of the columns. Each column has a *columnId* which
is taken from the field name. Column spans are noted in '..'-notation.
Rowspans are indicated by putting column identifiers in parantheses.
Examples:

=======================  ==============     ========
 field name              columnId           remark
=======================  ==============     ========
 \:fname: First name     'fname'
 \:lname: Last name      'lname'
 \:city:  City           'city'
 \:fname..city:                             column span from 'fname' to 'city'
 \:1:     One            '1'
 \:2:     Two            '2'
 \:3:     Three          '3'
 \:(3):                                     rowspan - column '3' is continued
 \:1..3:                                    column span from '1' to '3'
 \:(1..3):                                  rowspan - colspan '1' to '3' is continued
 \:A and B:              'A and B'
 \:yes - me too:         'yes - me too'
=======================  ==============     ========


Defining columns: column ids, widths, alignments
------------------------------------------------
There may be a column widths and an alignment
specification as well. They have to follow the columnId
and must be separated by commas. Syntax:
``:NAME[,[WIDTH][,[ALIGNMENT]]]``.

Examples:

=======================  ==============  ==========  ===============
 field name              column id       width       alignment
=======================  ==============  ==========  ===============
 \:abc , ,:              'abc'           default     default
 \:abc,,,,:              'abc'           default     default
 \:abc,,,,  :            error!
 \:abc,10:               'abc'           10          default
 \:abc,,t r:             'abc'           default     top right
 \:abc,60,bottom:        'abc'           60          bottom
 \:abc,,L M:             'abc'           60          left middle
=======================  ==============  ==========  ===============

The total width of a table row defaults to 100. It may be set to another
positive integer value using the 'total-width' option of the t3-field-list-table
directive.

The **width** of columns without explicit specification will be assigned
automatically. Any remaining free space in the row equally be distributed
to those columns.

**Alignment** is explained elsewhere in this document.


Options for the t3-field-list-table directive
---------------------------------------------
::

    option_spec = {
        'class'          : directives.class_option,
        'name'           : directives.unchanged,
        'header-rows'    : directives.nonnegative_int,
        'stub-columns'   : directives.nonnegative_int,

        'definition-row' : yes_no_zero_one,
        'total-width'    : directives.nonnegative_int,
        'allow-comments' : yes_no_zero_one,
        'debug-cellinfo' : yes_no_zero_one,
        'transformation' : yes_no_zero_one,
    }

class, name, header-rows, stub-columns
   These work the same way as described for `list-table`__.
   In short:

   - class: The class name. Is rendered as ``class="..."`` in HTML.
   - name: A name. Is rendered as ``id="..."`` in HTML.
   - header-rows: A positive integer. Determines
     how many rows will make up the table header.
   - stub-columns: Is a positive integer. Determines
     how many columns to the left are "header" columns.

__ http://docutils.sourceforge.net/docs/ref/rst/directives.html#list-table

definition-row
   May be True or False. Default is False. Takes one
   of the values 'yes', '1', 'no', '0'. If True,
   the first row of the table is treated as 'definition-row'
   as usual but is not shown in output.

total-width
   Is a positive integer. Default is 100. It sets the maximum for the total
   width of all columns.

allow-comments
   May be True or False. Default is True. Takes one
   of the values 'yes', '1', 'no', '0'. If False,
   items will never be treated as comment even if the
   field name consists of a sequence of on punctuation
   character only.

debug-cellinfo
   May be True or False. Default is False. Takes one
   of the values 'yes', '1', 'no', '0'. If switched
   on it will display information about table cells
   right in the table cell itself. This mainly aims
   at developers but may be useful to users of
   the t3-field-list-table directive as well.

transformation
   May be True or False. Default is True. Takes one
   of the values 'yes', '1', 'no', '0'. If set to
   False it will put the directive in "pass through"
   mode. The transformation of the nested list
   structure into the table structure is skipped
   and the list structure is returned unaltered.

   If provided by the application the commandline
   option ``--t3-field-list-table-off`` has the same effect.
   It has higher priority and affects all 't3-field-list-tables'.
