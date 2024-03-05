.. include:: /Includes.rst.txt

.. _Codeblocks:

==================
Codeblocks
==================

.. contents:: This page
   :backlinks: top
   :class: compact-list
   :depth: 99
   :local:


Basic examples
==============

.. highlight:: shell

List files, 'let see'::

   ls

Show more::

   ls -al

Or short::

   ls -1


Code-block with line numbers
============================

*Note:* When you select code, the linenumbers should not be selected, also they
are technically WITHIN the html that is selected. See :theme-issue:`149`.

Select some of the following code to test whether the line number get selected
as well.

.. code-block:: rst
   :caption: Example of 'contents' directive
   :linenos:
   :emphasize-lines: 2,3
   :force:

   This is an example block. Next two line have 'emphasis' background color.
   With another line.
   And a third one.

   .. code-block:: rst
      :caption: Example of 'contents' directive
      :linenos:
      :emphasize-lines: 2,3
      :force:

      This is an example block.
      With another line.
      And a third one.


Image and code-block without caption
====================================

.. image:: ../images/loremipsum/a4.jpg

.. code-block:: none

   .
   ├── composer.json
   ├── ext_emconf.php
   .



Image and code-block with caption
=================================

Code blocks with caption show up in html as
:html:`div.literal-block-wrapper.docutils.container` due to `docutils`.
Since DRC v3.0.dev2 html post-processing is changing this to
`div.literal-block-wrapper.docutils.du-container`. This should prevent
`sphinx_typo3_theme issue #148
<https://github.com/TYPO3-Documentation/sphinx_typo3_theme/issues/148>`__.


.. image:: ../images/loremipsum/a4.jpg

.. code-block:: none
   :caption: Generated extension with boilerplate code

   .
   ├── composer.json
   ├── ext_emconf.php
   .


Figure and code-block without caption
=====================================

.. figure:: ../images/loremipsum/a4.jpg

   Caption of image

.. code-block:: none

   .
   ├── composer.json
   ├── ext_emconf.php
   .


Figure and code-block with caption
==================================

.. figure:: ../images/loremipsum/a4.jpg

   Caption of image

.. code-block:: none
   :caption: Generated extension with boilerplate code

   .
   ├── composer.json
   ├── ext_emconf.php
   .


Code-block without caption within figure's caption
==================================================

.. figure:: ../images/loremipsum/a4.jpg

   Caption of image

   .. code-block:: none

      .
      ├── composer.json
      ├── ext_emconf.php
      .


Code-block with caption within figure's caption
===============================================

.. figure:: ../images/loremipsum/a4.jpg

   Caption of image

   .. code-block:: none
      :caption: Generated extension with boilerplate code

      .
      ├── composer.json
      ├── ext_emconf.php
      .
