
.. include:: /Includes.rst.txt
.. highlight:: rst

=====================
List Items as Buttons
=====================

On this page:

.. rst-class:: compact-list

.. contents:: This page
   :backlinks: top
   :class: compact-list
   :depth: 99
   :local:


.. attention::

   **Pitfall!**

   With pure Docutils the directive for specifying CSS classes is `class`.
   With Sphinx you have to use `.. rst-class:: ...` instead because `class`
   is already used in the Sphinx-Python-domain.


How does it work?
=================

The *t3SphinxThemeRtd* theme for TYPO3 comes along with style that make
the items of an unordered list appear as "buttons". To put this into
action the `<ul>` tag needs to have one of the following (css-) classes.

To assign a class place a `.. rst-class:: THENAME` right in front of
the list like so::

   .. rst-class:: horizbuttons-attention-m

   - horizbuttons-attention-m
   - two
   - three `with link <#>`__


Available styles
================

horizbuttons-attention-m
------------------------

Like admonition *attention* (blue)

.. rst-class:: horizbuttons-attention-m

- horizbuttons-attention-m
- two
- three `with link <#>`__


horizbuttons-important-m
------------------------

Like admonitions *error*, *important* (yellow)

.. rst-class:: horizbuttons-important-m

- horizbuttons-important-m
- two
- three `with link <#>`__


horizbuttons-note-m
-------------------

Like admonitions *generic*, *note*, *see also* (neutral, grey)

.. rst-class:: horizbuttons-note-m

- horizbuttons-note-m
- two
- three `with link <#>`__


horizbuttons-primary-m
-----------------------

Use the primary = key color (TYPO3 orange)

.. rst-class:: horizbuttons-primary-m

- horizbuttons-primary-m
- two
- three `with link <#>`__


horizbuttons-striking-m
-----------------------

Shall be very striking and unusual, something to not be be overseen.

.. rst-class:: horizbuttons-striking-m

- horizbuttons-striking-m
- two
- three `with link <#>`__


horizbuttons-tip-m
------------------

Like admonitions *hint*, *tip* (green)

.. rst-class:: horizbuttons-tip-m

- horizbuttons-tip-m
- two
- three `with link <#>`__


horizbuttons-warning-m
----------------------

Like admonitions *caution*, *danger*, *warning* (red)

.. rst-class:: horizbuttons-warning-m

- horizbuttons-danger-m
- two
- three `with link <#>`__


horizbuttons-attention-xxl
--------------------------

Like admonition *attention* (blue)

.. rst-class:: horizbuttons-attention-xxl

- horizbuttons-attention-xxl
- two
- three `with link <#>`__


horizbuttons-important-xxl
--------------------------

Like admonitions *error*, *important* (yellow)

.. rst-class:: horizbuttons-important-xxl

- horizbuttons-important-xxl
- two
- three `with link <#>`__


horizbuttons-note-xxl
---------------------

Like admonitions *generic*, *note*, *see also* (neutral, grey)

.. rst-class:: horizbuttons-note-xxl

- horizbuttons-note-xxl
- two
- three `with link <#>`__


horizbuttons-primary-xxl
------------------------

Use the primary = key color (TYPO3 orange)

.. rst-class:: horizbuttons-primary-xxl

- horizbuttons-primary-xxl
- two
- three `with link <#>`__


horizbuttons-striking-xxl
-------------------------

Shall be very striking and unusual, something to not be be overseen.

.. rst-class:: horizbuttons-striking-xxl

- horizbuttons-striking-xxl
- two
- three `with link <#>`__


horizbuttons-tip-xxl
--------------------

Like admonitions *hint*, *tip* (green)

.. rst-class:: horizbuttons-tip-xxl

- horizbuttons-tip-xxl
- two
- three `with link <#>`__


horizbuttons-warning-xxl
------------------------

Like admonitions *caution*, *danger*, *warning* (red)

.. rst-class:: horizbuttons-warning-xxl

- horizbuttons-danger-xxl
- two
- three `with link <#>`__


horizbuttons-attention-xxxl
---------------------------

Like admonition *attention* (blue)

.. rst-class:: horizbuttons-attention-xxxl

- horizbuttons-attention-xxxl
- two
- three `with link <#>`__


horizbuttons-important-xxxl
---------------------------

Like admonitions *error*, *important* (yellow)

.. rst-class:: horizbuttons-important-xxxl

- horizbuttons-important-xxxl
- two
- three `with link <#>`__


horizbuttons-note-xxxl
----------------------

Like admonitions *generic*, *note*, *see also* (neutral, grey)

.. rst-class:: horizbuttons-note-xxxl

- horizbuttons-note-xxxl
- two
- three `with link <#>`__


horizbuttons-primary-xxxl
-------------------------

Use the primary = key color (TYPO3 orange)

.. rst-class:: horizbuttons-primary-xxxl

- horizbuttons-primary-xxxl
- two
- three `with link <#>`__


horizbuttons-striking-xxxl
--------------------------

Shall be very striking and unusual, something to not be be overseen.

.. rst-class:: horizbuttons-striking-xxxl

- horizbuttons-striking-xxxl
- two
- three `with link <#>`__


horizbuttons-tip-xxxl
---------------------

Like admonitions *hint*, *tip* (green)

.. rst-class:: horizbuttons-tip-xxxl

- horizbuttons-tip-xxxl
- two
- three `with link <#>`__


horizbuttons-warning-xxxl
-------------------------

Like admonitions *caution*, *danger*, *warning* (red)

.. rst-class:: horizbuttons-warning-xxxl

- horizbuttons-danger-xxxl
- two
- three `with link <#>`__
