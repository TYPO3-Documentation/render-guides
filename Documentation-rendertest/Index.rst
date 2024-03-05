.. include:: /Includes.rst.txt

==========================
TYPO3 Theme Rendering Test
==========================

This is taken from this repository:

https://typo3-documentation.github.io/sphinx_typo3_theme_rendering_test/Admonitions-and-buttons/Index.html

This documentation is meant to provide a set of directives and their
rendering output that we may want to address.

The rendering process can be triggered via:

Docker
------

..  code:: bash

    docker run --rm --pull always -v ./:/project/ \
      ghcr.io/typo3-documentation/render-guides:latest \
      --no-progress Documentation-rendertest

via Makefile (Docker)
---------------------

..  code:: bash

    make rendertest

via Makefile (local, using custom CSS)
--------------------------------------

..  code:: bash

    make rendertest ENV=local

Within ddev
-----------

..  code:: bash

    composer make rendertest


-----

.. rubric:: Pages

.. rst-class::  compact-list
.. toctree::
   :titlesonly:
   :glob:

   */Index
   *