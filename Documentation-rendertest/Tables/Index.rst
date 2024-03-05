.. include:: /Includes.rst.txt

==============
Tables
==============

.. contents:: This page
   :backlinks: top
   :class: compact-list
   :depth: 99
   :local:



Giant tables
============

+------------+------------+-----------+------------+------------+-----------+------------+------------+-----------+------------+------------+-----------+
| Header 1   | Header 2   | Header 3  | Header 1   | Header 2   | Header 3  | Header 1   | Header 2   | Header 3  | Header 1   | Header 2   | Header 3  |
+============+============+===========+============+============+===========+============+============+===========+============+============+===========+
| body row 1 | column 2   | column 3  | body row 1 | column 2   | column 3  | body row 1 | column 2   | column 3  | body row 1 | column 2   | column 3  |
+------------+------------+-----------+------------+------------+-----------+------------+------------+-----------+------------+------------+-----------+
| body row 1 | column 2   | column 3  | body row 1 | column 2   | column 3  | body row 1 | column 2   | column 3  | body row 1 | column 2   | column 3  |
+------------+------------+-----------+------------+------------+-----------+------------+------------+-----------+------------+------------+-----------+
| body row 1 | column 2   | column 3  | body row 1 | column 2   | column 3  | body row 1 | column 2   | column 3  | body row 1 | column 2   | column 3  |
+------------+------------+-----------+------------+------------+-----------+------------+------------+-----------+------------+------------+-----------+
| body row 1 | column 2   | column 3  | body row 1 | column 2   | column 3  | body row 1 | column 2   | column 3  | body row 1 | column 2   | column 3  |
+------------+------------+-----------+------------+------------+-----------+------------+------------+-----------+------------+------------+-----------+



t3fieldlisttable
================

See :ref:`sphinxcontrib-t3fieldlisttable`!



A t3-field-list-table
=====================

.. t3-field-list-table::
 :header-rows: 1

 - :a: Demo A
   :b: Demo B
   :c: Demo C
   :d: Demo D

 - :a: a
   :b: b
   :c: c
   :d: d

 - :a: a
   :b: b
   :c: c
   :d: d

 - :a: a
   :b: b
   :c: c
   :d: d



A table in grid notation
========================

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
