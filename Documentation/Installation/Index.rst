..  include:: ../Includes.rst.txt

..  _installation:

=============
Installation
=============

This project is not an typo3 extension, but a stand alone application.
used to render documentation. If you want to learn more about how to write
documentation, please check the typo3 website.

.. add a link to the typo3 website using interlinking?

We provide 2 methods to install the project on your local machine.

- Using Docker
- Using PHP

.. note::

    The docker image is the recommended way to use this project. It will
    automatically install all dependencies and will not interfere with your
    local PHP installation.

Docker
------

The docker image is available on github packages. You can pull the image with
the following command.

::

    docker pull ghcr.io/typo3-documentation/render-guides:main

For all available tags, please check the `github packages page`_.
Once you have pulled the image, you can run the image to render your projects documentation.

::

    docker run --rm -v $(pwd):/project ghcr.io/typo3-documentation/render-guides:main --progress

Unlike other docker images this image will detect the owner-user of the mounted
project. This means that the files created by the docker image will have the
same owner as the files in your project. No more permission issues.

PHP
---

<TBD>

.. _`github packages page`: https://github.com/TYPO3-Documentation/render-guides/pkgs/container/render-guides
