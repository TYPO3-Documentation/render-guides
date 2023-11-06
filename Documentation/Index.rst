
=============
render-guides
=============

Mono-repo setup
===============

This repository is following a mono-repo setup. This means all code and
configuration to render documentation is in this repository. This includes
scripts to build the documentation, the configuration for the CI/CD pipeline.

To ensure the mono-repo setup works, and also works in separate repositories,
we are using a tool called monorepo-builder_. This tool will help us to keep the
dependencies over packages in sync.

If you add a new dependency to a package, you can run

::
    composer run monorepo:merge

This will update the root composer.json file with the new dependency.

We do recommend to run the validation check before you commit.

::
    composer run monorepo:validate

.. _monorepo-builder: https://github.com/symplify/monorepo-builder

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
    docker build -t render-guides:local .

Using PHP
---------

We do ship a phar_ binary with this repository. In short a phar file is an
executable PHP file. You can run it like any other executable.

To build the phar file we use box_, with some wrapper script. To build the phar
file you can run the following command.

::
    composer run build:phar

This will create a file called guides.phar in the build directory.

.. warning::

    Currently box is not able to build a phar file for projects containing
    composer plugins as it will only install production dependencies. This
    means that the composer.json is modified during the build process. You shall
    not commit this change to the repository.

.. _phar: https://www.php.net/manual/en/intro.phar.php
.. _box: https://box-project.github.io/box/
