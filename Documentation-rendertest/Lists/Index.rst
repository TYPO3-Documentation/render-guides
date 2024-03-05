.. include:: /Includes.rst.txt

=====
Lists
=====

.. contents:: This page
   :backlinks: top
   :class: compact-list
   :depth: 99
   :local:


Lists within admonitions
========================

.. important::

    wanna play a game?

    - inside
    - this

      - list
      - ``in the world``

        - hi
        - his

          hi


A demo list
===========

- here

  - is
  - some

    - list
    - items
    - `yahoo <http://www.yahoo.com>`_
    - ``huh``
- how
- ``inline literal``
- ``inline literal``
- ``inline literal``


Another demo list
=================

1. Typesetting is the composition of text by means of arranging physical
   types[1] or the digital equivalents. Stored letters and other symbols
   (called sorts in mechanical systems and glyphs in digital systems)
   are retrieved and ordered according to a language's orthography for
   visual display.

2. Typesetting is the composition of text by means of arranging physical
   types[1] or the digital equivalents. Stored letters and other symbols
   (called sorts in mechanical systems and glyphs in digital systems)
   are retrieved and ordered according to a language's orthography for
   visual display.

3. Typesetting is the composition of text by means of arranging physical
   types[1] or the digital equivalents. Stored letters and other symbols

   #. Abc
   #. Typesetting is the composition of text by means of arranging physical
      types[1] or the digital equivalents. Stored letters and other symbols
      (called sorts in mechanical systems and glyphs in digital systems)
      are retrieved and ordered according to a language's orthography for
      visual display.

   #. Cde

      Typesetting is the composition of text by means of arranging physical
      types[1] or the digital equivalents. Stored letters and other symbols

      #. Mno Typesetting is the composition of text by means of
         arranging physical types[1] or the digital equivalents.
         Stored letters and other symbols

      #. Nop Typesetting is the composition of text by means of arranging physical
         types[1] or the digital equivalents. Stored letters and other symbols
         (called sorts in mechanical systems and glyphs in digital systems)

         - Klm
         - Lmn
         - Mno

         are retrieved and ordered according to a language's orthography for
         visual display.

      #. Opq

      (called sorts in mechanical systems and glyphs in digital systems)
      are retrieved and ordered according to a language's orthography for
      visual display.

   (called sorts in mechanical systems and glyphs in digital systems)
   are retrieved and ordered according to a language's orthography for
   visual display.




Third demo list
===============

- here is a list in a second-level section.
- `yahoo <http://www.yahoo.com>`_
- `yahoo <http://www.yahoo.com>`_

  - `yahoo <http://www.yahoo.com>`_
  - here is an inner bullet ``oh``

    - one more ``with an inline literally``. `yahoo <http://www.yahoo.com>`_

      heh heh. child. try to beat this embed:

      .. literalinclude: : ../test_py_module/test.py
          :language: python
          :linenos:
          :lines: 1-10
  - and another. `yahoo <http://www.yahoo.com>`_
  - `yahoo <http://www.yahoo.com>`_
  - ``hi``
- and hehe


But deeper down the rabbit hole
===============================

- I kept saying that, "deeper down the rabbit hole". `yahoo <http://www.yahoo.com>`_

  - I cackle at night `yahoo <http://www.yahoo.com>`_.
- I'm so lonely here in GZ ``guangzhou``
- A man of python destiny, hopes and dreams. `yahoo <http://www.yahoo.com>`_

  - `yahoo <http://www.yahoo.com>`_

    - `yahoo <http://www.yahoo.com>`_ ``hi``
    - ``destiny``


.. index:: css; compact-list

Compact Lists
=============

Keywords: css, compact-list

Add CSS class `compact-list` to lists to get `li` tags with `margin-top:0; margin-bottom:0`.

In case of a normal list:

.. code-block:: rst

   .. rst-class:: compact-list

   -  abc
   -  bcd
   -  cde


.. t3-field-list-table::
 :header-rows: 1

 - :a: compact list
   :b: normal list

 - :a: .. rst-class:: compact-list

       -  abc
       -  bcd
       -  cde
       -  def
       -  efg

   :b: -  abc
       -  bcd
       -  cde
       -  def
       -  efg

 - :a: .. rst-class:: compact-list

       1. one
       2. two
       3. three

   :b: 1. one
       2. two
       3. three


Or, for example:

.. code-block:: rst

   .. rst-class:: compact-list

   1. one
   2. two
   3. three

Should look like:

.. rst-class:: compact-list

1. one
2. two
3. three


In case of `.. toctree::`:

.. code-block:: rst

   .. rst-class:: compact-list
   .. troctree::

      Abc/Index
      Bcd/Index

In case of `.. contents::`:

.. code-block:: rst

   .. contents::
      :class: compact-list

