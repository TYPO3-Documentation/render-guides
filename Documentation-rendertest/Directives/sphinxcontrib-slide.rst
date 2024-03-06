
.. include:: /Includes.rst.txt

.. _sphinxcontrib-slide:

===================
sphinxcontrib-slide
===================

Navigate this page:

.. contents:: This page
   :backlinks: top
   :class: compact-list
   :depth: 99
   :local:


What is it?
   This is a sphinx extension for embedding your presentation slides.

Code
   See https://github.com/TYPO3-Documentation/sphinx-contrib-slide
   and branch `develop
   <https://github.com/TYPO3-Documentation/sphinx-contrib-slide/tree/develop>`__.

What can be embedded?
   #. `Google docs <https://docs.google.com/>`_ documents
   #. `Google docs`_ presentations
   #. `Google docs`_ spreadsheets
   #. `Slides.com <https://slides.com/>`_ presentations
   #. `Slideshare <https://www.slideshare.net/>`_ presentations
   #. `Speakerdeck <https://speakerdeck.com/>`_ presentations

Syntax
   `.. slide:: URL`

   URL must be one of these::

      https://docs.google.com/document/d/…
      https://docs.google.com/presentation/d/…
      https://docs.google.com/spreadsheets/d/…
      https://slides.com/…          or http://…
      https://speakerdeck.com/…
      https://www.slideshare.net/…  or http://…

Finding the URL
   For Google docs go to ① "File", ② "Publish to the web", ③ and "Link"
   or ④ copy the basic part of the link from your browser.



Example: google document
========================

Link: https://docs.google.com/document/d/e/2PACX-1vR-lBF77A6YgK77uE8wzxFNbtxnS98I3DXSMW5qajO02QfkIc5vAdi10_iJMvXAmPJvv2Sedo_HllHE/pub

Source::

   .. slide:: https://docs.google.com/document/d/e/2PACX-1vR-lBF77A6YgK77uE8wzxFNbtxnS98I3DXSMW5qajO02QfkIc5vAdi10_iJMvXAmPJvv2Sedo_HllHE/pub

Rendered:

.. slide:: https://docs.google.com/document/d/e/2PACX-1vR-lBF77A6YgK77uE8wzxFNbtxnS98I3DXSMW5qajO02QfkIc5vAdi10_iJMvXAmPJvv2Sedo_HllHE/pub




Example: google presentation
============================


Link: https://docs.google.com/presentation/d/1FOVjpJIJrHC4Wly9rsGelDdfXe4bgamLOJh1GlVx2Tk

Source::

   .. slide:: https://docs.google.com/presentation/d/1FOVjpJIJrHC4Wly9rsGelDdfXe4bgamLOJh1GlVx2Tk

Rendered:

.. slide:: https://docs.google.com/presentation/d/1FOVjpJIJrHC4Wly9rsGelDdfXe4bgamLOJh1GlVx2Tk





Example: google docs spreadsheet
================================

Link: https://docs.google.com/spreadsheets/d/e/2PACX-1vRBBypnQdGwdTCq6Xz2EJyySm1v_Q0XndMlmFwHgjBAbxHuVQGNgch3qr9neSX66GjSAA_x8tZldqD5/pubhtml

Source::

   .. slide:: https://docs.google.com/spreadsheets/d/e/2PACX-1vRBBypnQdGwdTCq6Xz2EJyySm1v_Q0XndMlmFwHgjBAbxHuVQGNgch3qr9neSX66GjSAA_x8tZldqD5/pubhtml

Rendered:

.. slide:: https://docs.google.com/spreadsheets/d/e/2PACX-1vRBBypnQdGwdTCq6Xz2EJyySm1v_Q0XndMlmFwHgjBAbxHuVQGNgch3qr9neSX66GjSAA_x8tZldqD5/pubhtml




Example: slides.com
===================

Link: https://slides.com/bolajiayodeji/introduction-to-version-control-with-git-and-github

Source::

   .. slide:: https://slides.com/bolajiayodeji/introduction-to-version-control-with-git-and-github

Rendered:

.. slide:: https://slides.com/bolajiayodeji/introduction-to-version-control-with-git-and-github





Example: slideshare.net
=======================

Link: https://www.slideshare.net/TedTalks/physics-45280434

Source::

   .. slide:: https://www.slideshare.net/TedTalks/physics-45280434

Rendered:

.. slide:: https://www.slideshare.net/TedTalks/physics-45280434




Example: speakerdeck.com
========================

Link: https://speakerdeck.com/oliverklee/test-driven-development-with-phpunit-1

Source::

   .. slide:: https://speakerdeck.com/oliverklee/test-driven-development-with-phpunit-1

Rendered:

.. slide:: https://speakerdeck.com/oliverklee/test-driven-development-with-phpunit-1

