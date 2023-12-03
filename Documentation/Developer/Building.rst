..  include:: ../Includes.rst.txt

..  _Building:

========
Building
========

While the components of this repository are split into separate packages, it can
be build as a standalone application. Two ways are provided to use this project
on your own environment.

Using Docker
------------

A Docker image is available on `GitHub packages`_. If you want to build your own
image you can use the following command, in the root of this repository.

..  code-block:: shell

    make docker-build

Once the build is finished you can execute your own image using:

..  code-block:: shell

    docker run --rm -v $(pwd):/project typo3-docs:local --progress

For macOS you also need to specify the argument ``user``:

..  code-block:: shell

    docker run --rm -v $(pwd):/project --user=$(id -u):$(id -g) typo3-docs:local --progress

Using PHP
---------

A phar_ binary is shipped with this repository. In short, a phar file is an
executable PHP file. You can run it like any other executable.

To build the phar file we use Box_, with some wrapper script. To build the phar
file yourself, you can run the following command.

..  code-block:: shell

    make build-phar

This will create a file called :file:`guides.phar` in the build directory. You
can execute the phar file like a PHP file using:

..  code-block:: shell

    php build/guides.phar

..  warning::

    Currently, Box is not able to build a phar file for projects containing
    Composer plugins as it will only install production dependencies. This
    means that the :file:`composer.json` is modified during the build process.
    You shall not commit this change to the repository.

.. _phar: https://www.php.net/manual/en/intro.phar.php
.. _Box: https://box-project.github.io/box/
.. _`GitHub packages`: https://github.com/TYPO3-Documentation/render-guides/pkgs/container/render-guides
