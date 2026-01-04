.. include:: /Includes.rst.txt
.. highlight:: rst
.. index:: Line blocks
.. _Line-blocks:

===========
Line blocks
===========

This example is taken from `Docutils: Line Blocks`__.

__ http://docutils.sourceforge.net/docs/ref/rst/restructuredtext.html#line-blocks>`__

   Doctree elements: line_block, line.  (New in Docutils 0.3.5.)

   Line blocks are useful for address blocks, verse (poetry, song
   lyrics), and unadorned lists, where the structure of lines is
   significant.  Line blocks are groups of lines beginning with vertical
   bar ("|") prefixes.  Each vertical bar prefix indicates a new line, so
   line breaks are preserved.  Initial indents are also significant,
   resulting in a nested structure.  Inline markup is supported.
   Continuation lines are wrapped portions of long lines; they begin with
   a space in place of the vertical bar.  The left edge of a continuation
   line must be indented, but need not be aligned with the left edge of
   the text above it.  A line block ends with a blank line.


Syntax diagram
--------------

.. code-block:: none

   +------+-----------------------+
   | "| " | line                  |
   +------| continuation line     |
          +-----------------------+



Example: Continuation lines
---------------------------

Source
~~~~~~

This example illustrates continuation lines::

   |  Lend us a couple of bob till Thursday.
   |  I'm absolutely skint.
   |  But I'm expecting a postal order and I can pay you back
      as soon as it comes.
   |  Love, Ewan.

Result
~~~~~~

This example illustrates continuation lines:

|  Lend us a couple of bob till Thursday.
|  I'm absolutely skint.
|  But I'm expecting a postal order and I can pay you back
   as soon as it comes.
|  Love, Ewan.


Example: Nesting of line blocks
-------------------------------

Source
~~~~~~

This example illustrates the nesting of line blocks, indicated by the
initial indentation of new lines::

   Take it away, Eric the Orchestra Leader!

   |  A one, two, a one two three four
   |
   |  Half a bee, philosophically,
   |     must, *ipso facto*, half not be.
   |  But half the bee has got to be,
   |     *vis a vis* its entity.  D'you see?
   |
   |  But can a bee be said to be
   |     or not to be an entire bee,
   |        when half the bee is not a bee,
   |           due to some ancient injury?
   |
   |  Singing...

Result
~~~~~~

Take it away, Eric the Orchestra Leader!

|  A one, two, a one two three four
|
|  Half a bee, philosophically,
|     must, *ipso facto*, half not be.
|  But half the bee has got to be,
|     *vis a vis* its entity.  D'you see?
|
|  But can a bee be said to be
|     or not to be an entire bee,
|        when half the bee is not a bee,
|           due to some ancient injury?
|
|  Singing...



Example: "Crazy" indentation levels
-----------------------------------

If the lines of a line block have different indentations, each unique
indentation counts as one indentation level. The order in which levels are
created does not matter. Nor does it matter, how many blanks are used to create
a level. In the output the indentation size is the same for all levels.


Source
~~~~~~

An example with "crazy" indentations::

   .. 01   2     3         4   indentation level
   .. ⬇⬇   ⬇     ⬇         ⬇

   |                       At indentation level 4
   |             At indentation level 3
   |       At indentation level 2
   |   At indentation level 1
   |  At indentation level 0
   |       At indentation level 2
   |                       At indentation level 4
   |             At indentation level 3
   |  At indentation level 0
   |   At indentation level 1


Result
~~~~~~

.. 01   2     3         4   indentation level
.. ⬇⬇   ⬇     ⬇         ⬇

|                       At indentation level 4
|             At indentation level 3
|       At indentation level 2
|   At indentation level 1
|  At indentation level 0
|       At indentation level 2
|                       At indentation level 4
|             At indentation level 3
|  At indentation level 0
|   At indentation level 1
