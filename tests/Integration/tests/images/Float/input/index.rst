===========
Float Tests
===========

Align Left
==========

.. figure:: /typo3-logo.png
   :alt: Left figure
   :align: left

   Left aligned figure

Align Right
===========

.. figure:: /typo3-logo.png
   :alt: Right figure
   :align: right

   Right aligned figure

Align Center
============

.. figure:: /typo3-logo.png
   :alt: Center figure
   :align: center

   Center aligned figure

Legacy Figure Class
===================

.. figure:: /typo3-logo.png
   :alt: Legacy float-left figure
   :class: float-left

   Should rewrite to float-start

Legacy Figure Right
====================

.. figure:: /typo3-logo.png
   :alt: Legacy float-right figure
   :class: float-right

   Should rewrite to float-end

Legacy Image Left
=================

.. image:: /typo3-logo.png
   :alt: Legacy float-left image
   :class: float-left

Legacy Image Right
==================

.. image:: /typo3-logo.png
   :alt: Legacy float-right image
   :class: float-right

Mixed Legacy With Extra Class
==============================

.. figure:: /typo3-logo.png
   :alt: Mixed class figure
   :class: float-left with-shadow

   Should rewrite float-left but keep with-shadow

Align Plus Legacy Class
========================

.. figure:: /typo3-logo.png
   :alt: Both align and legacy class
   :align: left
   :class: float-left

   Should use align, class gets rewritten to float-start

Image Align Left
================

.. image:: /typo3-logo.png
   :alt: Image align left
   :align: left

Image Align Right
=================

.. image:: /typo3-logo.png
   :alt: Image align right
   :align: right

Modern Float Start (No Warning)
================================

.. figure:: /typo3-logo.png
   :alt: Modern float-start figure
   :class: float-start

   Already uses modern class, no rewriting needed

Modern Float End Image (No Warning)
=====================================

.. image:: /typo3-logo.png
   :alt: Modern float-end image
   :class: float-end

Substitution Image Left
========================

.. |sub-logo| image:: /typo3-logo.png
   :alt: Substitution logo
   :class: float-left

Use the |sub-logo| inline text here.
