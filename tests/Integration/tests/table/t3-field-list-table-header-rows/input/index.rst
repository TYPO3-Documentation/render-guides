===========
Header rows
===========

No header row:

..  t3-field-list-table::
    :header-rows: 0

    -   :A:   first
        :B:   second

    -   :A:   1
        :B:   two

Two header rows:

..  t3-field-list-table::
    :header-rows: 2

    -   :A:   first
        :B:   second

    -   :A:   third
        :B:   fourth

    -   :A:   1
        :B:   two

Without the option, the first row is the header:

..  t3-field-list-table::

    -   :A:   first
        :B:   second

    -   :A:   1
        :B:   two

An invalid value warns and keeps the first row as header:

..  t3-field-list-table::
    :header-rows: one

    -   :A:   first
        :B:   second

    -   :A:   1
        :B:   two
