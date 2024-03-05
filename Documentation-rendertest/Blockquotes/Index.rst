.. include:: /Includes.rst.txt
.. highlight:: rst
.. _Block-Quotes:


============
Block quotes
============

.. contents:: This page
   :backlinks: top
   :class: compact-list
   :depth: 99
   :local:


Famous quotes
=============

   Every revolutionary idea seems to evoke three stages of reaction. They may
   be summed up by the phrases: (1) It's completely impossible. (2) It's
   possible, but it's not worth doing. (3) I said it was a good idea all along.

   -- Arthur C. Clarke

   God created two acts of folly. First, He created the Universe in a Big Bang.
   Second, He was negligent enough to leave behind evidence for this act, in
   the form of microwave radiation.

   — PAUL ERDŐS, 1913 TO 1996, Mathematician


Nested quotes
=============

   God created two acts of folly. First, He created the Universe in a Big Bang.

      God created two acts of folly. First, He created the Universe in a Big Bang.

         God created two acts of folly. First, He created the Universe in a Big Bang.
         Second, He was negligent enough to leave behind evidence for this act, in
         the form of microwave radiation.

         -- PAUL ERDŐS, 1913 TO 1996, Mathematician

      Second, He was negligent enough to leave behind evidence for this act, in
      the form of microwave radiation.

      -- PAUL ERDŐS, 1913 TO 1996, Mathematician, attribution, very long,
         PAUL ERDŐS, 1913 TO 1996, Mathematician, attribution, very long,
         PAUL ERDŐS, 1913 TO 1996, Mathematician, attribution, very long,

   Second, He was negligent enough to leave behind evidence for this act, in
   the form of microwave radiation.

   -- PAUL ERDŐS, 1913 TO 1996, Mathematician


Element description
===================

Taken from `reStructuredText documentation
<https://docutils.sourceforge.io/docs/ref/rst/restructuredtext.html#block-quotes>`__.

Doctree element: block_quote, attribution.

A text block that is indented relative to the preceding text, without preceding
markup indicating it to be a literal block or other content, is a block quote.
All markup processing (for body elements and inline markup) continues within
the block quote::

   This is an ordinary paragraph, introducing a block quote.

      "It is my business to know things.  That is my trade."

      -- Sherlock Holmes

A block quote may end with an attribution: a text block beginning with "--",
"---", or a true em-dash, flush left within the block quote.  If the
attribution consists of multiple lines, the left edges of the second and
subsequent lines must align.

Multiple block quotes may occur consecutively if terminated with attributions.

   Unindented paragraph.

      Block quote 1.

      -- Attribution 1

      Block quote 2.

*Empty comments* may be used to explicitly terminate preceding constructs that
would otherwise consume a block quote::

   *  List item.

   ..


      Block quote 3.

Empty comments may also be used to separate block quotes::

      Block quote 4.

   ..

      Block quote 5.

Blank lines are required before and after a block quote, but these blank lines
are not included as part of the block quote.

Syntax diagram::

   +------------------------------+
   | (current level of            |
   | indentation)                 |
   +------------------------------+
      +---------------------------+
      | block quote               |
      | (body elements)+          |
      |                           |
      | -- attribution text       |
      |    (optional)             |
      +---------------------------+


Example
=======

This is an ordinary paragraph, introducing a block quote.

Source
------

.. code-block:: rst

   "It is my business to know things.
   That is my trade."

   -- Sherlock Holmes


Result
------

   "It is my business to know things.
   That is my trade."

   -- Sherlock Holmes
