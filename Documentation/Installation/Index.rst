..  include:: /Includes.rst.txt

..  _installation:

=============
Installation
=============

This project is not a TYPO3 extension, but a standalone application
used to render documentation. If you want to learn more about how to write
documentation, please check the
:ref:`Contributing Guide - How to Document <h2document:contribute>`.

Multiple methods are provided to install the project on your local machine.
You can choose whatever is easiest for you:

-   Using Docker natively, with a provided official container
-   Using Docker natively, with a locally-generated container
-   Using DDEV (utilizing Docker)
-   Using PHP

..  note::

    The Docker container is the recommended way to use this project for
    end-users. It will automatically set up all dependencies and will not interfere
    with your local PHP installation or project. The container can be
    used in any project (and in any GitHub action) without further dependencies.

..  _Setup_Docker:

Docker
------

The Docker image is available on GitHub packages. You can pull the image with
the following command.

..  code-block:: shell

    docker pull ghcr.io/typo3-documentation/render-guides:main

For all available tags, please check the `GitHub packages page`_.
Once you have pulled the image, you can run the image to render your project's
documentation.

Note that we will use `:stable` at a later point to always provide stable
images with a locked dependency and theme asset set.

..  code-block:: shell

    docker run --rm -v $(pwd):/project ghcr.io/typo3-documentation/render-guides:main --progress --config ./Documentation

Unlike other Docker images, this image will detect the owner-user of the mounted
project. This means that the files created by the Docker image will have the
same owner as the files in your project. No more permission issues should occur,
when files are getting generated inside the image.

If this fail, you can resort to specifying the user:

..  code-block:: shell

    docker run --rm --user=$(id -u):$(id -g) -v $(pwd):/project ghcr.io/typo3-documentation/render-guides:main --progress --config ./Documentation

The provided image allows you to also perform a few other actions:

..  code-block:: shell

    # Convert Settings.cfg to guides.xml:
    docker run --rm -v $(pwd):/project ghcr.io/typo3-documentation/render-guides:main migrate ./Documentation

    # Check guides.xml files for XML conformity
    docker run --rm -v $(pwd):/project ghcr.io/typo3-documentation/render-guides:main lint-guides-xml

    # Adapt guides.xml programmatically (work in progress)
    docker run --rm -v $(pwd):/project ghcr.io/typo3-documentation/render-guides:main configure \
      --project-version="2.2" \
      --project-title="My project title" \
      --project-release="2023" \
      --project-copyright="2000-2023" ./Documentation

In case of errors you can increase verbose output by prefixing any command with the argument
:bash:`verbose`:

..  code-block:: shell

    # Execute verbose commands with inline setting
    docker run --rm -v $(pwd):/project ghcr.io/typo3-documentation/render-guides:main verbose (render|migrate|lint-guides-xml|configure) [arguments/options]

    # Execute verbose commands with inline setting, useful for i.e. external actions
    SHELL_VERBOSITY=3 docker run --rm -v $(pwd):/project ghcr.io/typo3-documentation/render-guides:main (render|migrate|lint-guides-xml|configure) [arguments/options]

Another way to utilize Docker is to create your own image/container. This is aimed at people
who want to contribute to the underlying Documentation tool. Please see :ref:`_Building`
for those steps.

..  _Setup_DDEV:

DDEV
----

`DDEV <https://ddev.com/>`__ is a utility layer on top of Docker. It allows to easily
build and maintain local development instances with specific environments.

This project also ships a :file:`.ddev/` configuration directory, that allows
you to start a specific container in which you can render Documentation, and
have an environment where you can contribute to this repository without any
other requirement than Docker and DDEV.

To render the documentation you can run

..  code-block:: shell

    ddev start
    ddev composer install
    ddev composer make docs

..  _Setup_PHP:

PHP
---

If your host environment already has a PHP binary and is able to run Composer,
as well as interpret Makefile syntax (i.e. through a `build-essential` package),
you can create documentation natively, without needing docker.

You can run these commands locally:

..  code-block:: shell

    composer install
    make docs

The provided Symfony Commands can be executed via:

..  code-block:: shell

    ./packages/typo3-guides-cli/bin/typo3-guides (migrate|lint-guides-xml|configure)

.. _`GitHub packages page`: https://github.com/TYPO3-Documentation/render-guides/pkgs/container/render-guides
