..  include:: /Includes.rst.txt

==========================
TYPO3 theme rendering test
==========================

This is taken from this repository:

https://github.com/TYPO3-Documentation/sphinx_typo3_theme_rendering_test

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

..  rubric:: Pages

..  rst-class::  compact-list
..  toctree::
    :titlesonly:
    :glob:

    */Index
    *
