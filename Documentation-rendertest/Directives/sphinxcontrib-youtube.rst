
.. include:: /Includes.rst.txt

.. _sphinxcontrib-youtube:

=====================
sphinxcontrib-youtube
=====================

See https://github.com/TYPO3-Documentation/sphinx-contrib-youtube/blob/develop/README.rst

.. contents:: This page
   :backlinks: top
   :class: compact-list
   :depth: 99
   :local:


Youtube
=======

Code:

.. code-block:: rst

   .. only:: html or singlehtml

      .. youtube:: UdIYDZgBrQU

Result:

.. only:: html or singlehtml

   .. youtube:: UdIYDZgBrQU


sphinxcontrib.youtube README
============================

This module defines a directive, `youtube`.  It takes a single, required
argument, a YouTube video ID::

.. youtube:: oHg5SJYRHA0

.. youtube:: oHg5SJYRHA0

The referenced video will be embedded into HTML output.  By default, the
embedded video will be sized for 720p content.  To control this, the
parameters "aspect", "width", and "height" may optionally be provided::

   .. youtube:: oHg5SJYRHA0
      :width: 640
      :height: 480

.. youtube:: oHg5SJYRHA0
   :width: 640
   :height: 480

::

   .. youtube:: oHg5SJYRHA0
      :aspect: 4:3

.. youtube:: oHg5SJYRHA0
   :aspect: 4:3

::

   .. youtube:: oHg5SJYRHA0
      :width: 100%

.. youtube:: oHg5SJYRHA0
   :width: 100%

::

   .. youtube:: oHg5SJYRHA0
      :height: 200px

.. youtube:: oHg5SJYRHA0
   :height: 200px

