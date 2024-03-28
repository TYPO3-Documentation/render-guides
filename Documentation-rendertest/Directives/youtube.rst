
.. include:: /Includes.rst.txt

.. _youtube-directive:

=================
Youtube directive
=================

.. contents:: This page
   :backlinks: top
   :class: compact-list
   :depth: 99
   :local:


Youtube
=======

Code:

..  code-block:: rst

    ..  youtube:: UdIYDZgBrQU

Result:

..  youtube:: UdIYDZgBrQU


youtube directive parameters
============================

It takes a single, required argument, a YouTube video ID:

..  code-block:: rst

    .. youtube:: oHg5SJYRHA0

.. youtube:: oHg5SJYRHA0

The referenced video will be embedded into HTML output.  By default, the
embedded video will be sized for 720p content.  To control this, the
parameters "aspect", "width", and "height" may optionally be provided:


..  code-block:: rst

   .. youtube:: oHg5SJYRHA0
      :width: 640
      :height: 480

.. youtube:: oHg5SJYRHA0
   :width: 640
   :height: 480


..  code-block:: rst

   .. youtube:: oHg5SJYRHA0
      :aspect: 4:3

.. youtube:: oHg5SJYRHA0
   :aspect: 4:3


..  code-block:: rst

   .. youtube:: oHg5SJYRHA0
      :width: 100%

.. youtube:: oHg5SJYRHA0
   :width: 100%

..  code-block:: rst

   .. youtube:: oHg5SJYRHA0
      :height: 200px

.. youtube:: oHg5SJYRHA0
   :height: 200px
