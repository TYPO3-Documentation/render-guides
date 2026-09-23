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

..  tip::

    Did you know: Instead of the :bash:`docker` client you can also use
    the lightweight drop-in replacement `Podman <https://podman.io/>`__ to run
    the mentioned containers by replacing all :bash:`docker` commands in the
    following steps with :bash:`podman`.

..  _Setup_Docker:

Docker
------

The Docker image is available on GitHub packages. You can pull the image with
the following command.

..  code-block:: shell

    docker pull ghcr.io/typo3-documentation/render-guides:latest

For all available tags, please check the `GitHub packages page`_.
Once you have pulled the image, you can run the image to render your project's
documentation.

..  note::

    The Docker container internally contains a tagged release version of
    this repository, and use that version as well to reference asset
    URIs of the theme on our CDN.

..  code-block:: shell

    docker run --rm -v $(pwd):/project ghcr.io/typo3-documentation/render-guides:latest --progress --config ./Documentation

Unlike other Docker images, this image will detect the owner-user of the mounted
project. This means that the files created by the Docker image will have the
same owner as the files in your project. No more permission issues should occur,
when files are getting generated inside the image.

..  include:: ../_Includes/NoteProjectDirectoryName.rst.txt

If this fails, you can resort to specifying the user:

..  code-block:: shell

    docker run --rm --user=$(id -u):$(id -g) -v $(pwd):/project ghcr.io/typo3-documentation/render-guides:latest --progress --config ./Documentation

The provided image allows you to also perform a few other actions:

..  code-block:: shell

    # Convert Settings.cfg to guides.xml:
    docker run --rm -v $(pwd):/project ghcr.io/typo3-documentation/render-guides:latest migrate ./Documentation

    # Check guides.xml files for XML conformity
    docker run --rm -v $(pwd):/project ghcr.io/typo3-documentation/render-guides:latest lint-guides-xml

    # Adapt guides.xml programmatically (work in progress)
    docker run --rm -v $(pwd):/project ghcr.io/typo3-documentation/render-guides:latest configure \
      --project-version="2.2" \
      --project-title="My project title" \
      --project-release="2023" \
      --project-copyright="2000-2023" ./Documentation

..  _installation-single-markdown:

The whole manual as one Markdown file
=====================================

Every page is rendered to Markdown beside its HTML, which is what tools reading
a single page want. A tool that wants to read the *whole* manual at once --
a local language model being the obvious case -- is better served by one file.

Pass :bash:`--single-markdown` to get it:

..  code-block:: shell

    docker run --rm -v $(pwd):/project ghcr.io/typo3-documentation/render-guides:latest \
      --single-markdown ./Documentation

The result is a single :file:`Documentation-GENERATED-temp/singlemd/Index.md`
containing every page, in the order of the table of contents, separated by
horizontal rules, under one YAML front matter block describing the project.

A link to another page of the same manual points inside the file, because that
page is in it. Markdown cannot give a heading an id, so the file writes an empty
HTML anchor before each target. Only anchors that identify one place are
written: a heading such as "Configuration" occurs in many pages of a large
manual, and a link to it would land on whichever came first. Those links, and
every link to another manual, stay permalinks to :samp:`docs.typo3.org` and keep
working wherever the file is copied.

..  note::

    The option renders *only* that file: no HTML, and no per-page Markdown.
    That is what makes it quick enough to re-run whenever the documentation
    changes. Images the manual references are copied next to it, because the
    Markdown points at them and would otherwise point at nothing.

This is deliberately a local tool. Nothing publishes the file, and
:samp:`docs.typo3.org` does not carry it -- the published manuals offer the
per-page Markdown instead, which is linked from every page as
:html:`<link rel="alternate" type="text/markdown">`.

..  _installation-single-html:

The whole manual as one HTML page
=================================

For reading or printing a manual in one piece, pass :bash:`--single-html`:

..  code-block:: shell

    docker run --rm -v $(pwd):/project ghcr.io/typo3-documentation/render-guides:latest \
      --single-html ./Documentation

The result is :file:`Documentation-GENERATED-temp/singlehtml/Index.html`,
every page of the manual in the order of the table of contents, styled like
the rendered manual.

Like :bash:`--single-markdown`, the option renders *only* that file. Passing
both renders both files in one run.

:samp:`docs.typo3.org` does not carry this page either: it is no longer rendered
with every manual, and the rendered pages no longer link to it.

In case of errors you can increase verbose output by prefixing any command with the argument
:bash:`verbose`:

..  code-block:: shell

    # Execute verbose commands with inline setting
    docker run --rm -v $(pwd):/project ghcr.io/typo3-documentation/render-guides:latest verbose (render|migrate|lint-guides-xml|configure) [arguments/options]

    # Execute verbose commands with inline setting, useful for i.e. external actions
    SHELL_VERBOSITY=3 docker run --rm -v $(pwd):/project ghcr.io/typo3-documentation/render-guides:latest (render|migrate|lint-guides-xml|configure) [arguments/options]

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
