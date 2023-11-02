..  include:: ../Includes.rst.txt

..  _Building:

========
Building
========

While the components of this repository are split into separate packages, it can
be build as a stand alone application. We do provide 2 ways to use this project
on your own device.

Using Docker
------------

A docker image is available on github packages. If you want to build your own
image you can use the following command, in the root of this repository.

::

    docker build -t typo3-docs:local .

once the build is finished you can execute your fresh image using::

  docker run --rm -v $(pwd):/project typo3-docs:local --progress

Using PHP
---------

We do ship a phar_ binary with this repository. In short a phar file is an
executable PHP file. You can run it like any other executable.

To build the phar file we use box_, with some wrapper script. To build the phar
file you can run the following command.

::
    composer run build:phar

This will create a file called guides.phar in the build directory. You can execute
the phar file like a php file using::

    php build/guides.phar

.. warning::

    Currently box is not able to build a phar file for projects containing
    composer plugins as it will only install production dependencies. This
    means that the composer.json is modified during the build process. You shall
    not commit this change to the repository.

.. _phar: https://www.php.net/manual/en/intro.phar.php
.. _box: https://box-project.github.io/box/
.. _`github packages page`: https://github.com/TYPO3-Documentation/render-guides/pkgs/container/render-guides
