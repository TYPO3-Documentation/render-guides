
=============
render-guides
=============

This is the documentation rendering tool for TYPO3 projects. It is based on
`phpDocumentor/guides <https://github.com/phpDocumentor/guides>`__
and can be used as a drop-in replacement for Sphinx.
The tool is used by the automated documentation rendering system of the
TYPO3 project. And can be used by documentation authors to validate their
documentation.

Some basic commands are listed below, for more information, see the
`Documentation` subdirectory of this project.

Usage with Docker (via supplied container)
==========================================

::

    # Create output directory.
    mkdir -p Documentation-GENERATED-temp

    # Execute the Docker container that is provided remotely.
    # Renders all files in the `Documentation` and store in `Documentation-GENERATED-temp`.
    # On macOS you need to specify the parameter "--user=$(id -u):$(id -g)"
    # "/project" is a fixed directory name, not a placeholder.
    docker run --rm --pull always -v $(pwd):/project -it ghcr.io/typo3-documentation/render-guides:latest --config=Documentation

(see :ref:`_Setup_Docker:Docker containers` for complete documentation. You
can also use a specific version of the `render-guides` Docker container, i.e. `:1` for the latest `1.x` version.)


Usage with Docker (via custom container)
========================================

::

    # Build the custom local Docker container
    docker build --file Dockerfile --tag typo3-docs:local .

    # Execute the Docker container that is provided locally, build Documentation
    # On macOS you need to specify the parameter "--user=$(id -u):$(id -g)"
    # "/project" is a fixed directory name, not a placeholder.
    docker run --rm -v ${PWD}:/project -it typo3-docs:local --progress

(see :ref:`_Setup_Docker:Docker containers` for complete documentation)

You can inspect the created container by running a shell::

    docker run --entrypoint=sh -it --rm typo3-docs:local


Usage with DDEV
===============

::

    # Renders all files in the `Documentation` and store in `Documentation-GENERATED-temp`.
    ddev composer make docs

(see :ref:`_Setup_DDEV:DDEV` for complete documentation)

Usage with local PHP
====================

::

    # Renders all files in the `Documentation` and store in `Documentation-GENERATED-temp`.
    make docs

(see :ref:`_Setup_PHP:Local PHP` for complete documentation)

Contributing
============

See :ref:`Contributing` for more information.
