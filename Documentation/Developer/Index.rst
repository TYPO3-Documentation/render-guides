..  include:: /Includes.rst.txt

..  _Developers:

==========
Developers
==========

This part of the documentation is meant for developers working on the render-guides
project. You will find information about the setup of the project and the release
process.

Under the hood this tool is based on `phpDocumentor/guides`_

The repository ships a wide range of commands that are used to render the documentation,
build and execute development helpers, and they can be utilized by GitHub Actions.

The "single source of truth" for these commands is within the `Makefile`. You can
see all available commands via:

::

    make help

The most common commands are probably:

::

    # Render documentation
    make docs

    # Run tests
    make test

    # (Re)-Create Docker container
    make docker-build

Most `make` commands can be prepended or appended with the parameter `ENV=local`:

::

    # Render documentation
    make docs ENV=local

    # Run tests
    make test ENV=local

    # (Re)-Create Docker container
    make docker-build ENV=local

By default most `make` commands all utilize `Docker` to run within
a container.
If the parameter `ENV=local` is appended or prepended to the command, they can
also be run locally, without Docker. This can speed up processing and saves
resources on build pipelines. This requires `php` on your host.

The `Makefile` can also be executed via composer. All commands in the `make` range
are just passed onto the `Makefile` via a composer script, and automatically
then use the `ENV=local` scope:

::

    # Render documentation
    composer make docs

    # Run tests
    composer make test

    # (Re)-Create Docker container
    composer make docker-build

If your local environment does not provide `make` you can use DDEV to wrap the
same commands within the DDEV container:

::

    # Render documentation
    ddev composer make docs

    # Run tests
    ddev composer make test

    # (Re)-Create Docker container
    ddev composer make docker-build

All these options are provided to allow for maximum convenience for any contributor
and the GitHub pipelines, while internally only using `Makefile` syntax.

..  toctree::
    :maxdepth: 2
    :titlesonly:

    Mono-repository
    Directory-structure
    Building
    Contributing
    Theme-customization
