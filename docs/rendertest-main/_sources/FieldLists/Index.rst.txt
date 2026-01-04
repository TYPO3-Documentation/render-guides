.. include:: /Includes.rst.txt

===========
Field lists
===========

.. contents:: This page
   :backlinks: top
   :class: compact-list
   :depth: 99
   :local:


About field lists
=================

:Docutils:  `Docutils home <https://docutils.sourceforge.io/>`__
:Overview:  `Project documentation overview <https://docutils.sourceforge.io/docs/index.html>`__
:Reference: `Field lists <https://docutils.sourceforge.io/docs/ref/rst/restructuredtext.html#field-lists>`__


Example
=======

Source:

.. code-block:: rst

   :Date: 2001-08-16
   :Version: 1
   :Authors: - Me
             - Myself
             - I
   :Indentation: Since the field marker may be quite long, the second
      and subsequent lines of the field body do not have to line up
      with the first line, but they must be indented relative to the
      field name marker, and they must line up with each other.
   :Parameter i: integer

Result:

:Date: 2001-08-16
:Version: 1
:Authors: - Me
          - Myself
          - I
:Indentation: Since the field marker may be quite long, the second
   and subsequent lines of the field body do not have to line up
   with the first line, but they must be indented relative to the
   field name marker, and they must line up with each other.
:Parameter i: integer


