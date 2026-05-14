..  include:: /Includes.rst.txt

..  _override-templates:

====================
Overriding templates
====================

By default, the render-guides container uses the Twig templates shipped
with the official TYPO3 Documentation theme. You can provide your own
templates to customize or extend the rendering output **without modifying
the container image**.

..  important::

    Custom templates are only supported when you build the documentation
    **locally** (for example using Docker or DDEV) or within your **own
    CI/CD pipeline**.

    When your project documentation is built and deployed automatically via
    the **official TYPO3 documentation workflow** (to
    `https://docs.typo3.org <https://docs.typo3.org>`_), custom templates
    are **not supported**.

    The central rendering service uses a fixed, controlled version of the
    official theme to ensure consistency across all published manuals.

..  _template-override-mechanism:

How template overriding works
=============================

The TYPO3 Documentation theme registers a list of template search paths.
When rendering, the Twig template engine resolves templates by checking
these paths **in order** and using the first match.

When custom template directories are present, they are prepended to the
search path, giving them higher priority than the built-in templates.

The resolution order is:

#. Custom templates mounted via Docker volume at :file:`/templates`
   (highest priority)
#. Custom templates bundled in the project at
   :file:`resources/custom-templates` (relative to the project root)
#. TYPO3-specific theme templates
#. Bootstrap 5 theme templates (fallback)
#. Base phpDocumentor templates (fallback)

..  _template-docker-volume:

Method 1: mount a Docker volume
===============================

Mount a local directory into the container at the path :file:`/templates`.

For example, if your custom templates are stored in a folder named
:file:`my-custom-templates` in your current working directory:

..  code-block:: shell

    docker run --rm \
      --pull always \
      -v "$(pwd):/project" \
      -v "$(pwd)/my-custom-templates:/templates:ro" \
      -it ghcr.io/typo3-documentation/render-guides:latest \
      --progress --config=Documentation

..  note::

    The :file:`/templates` path is **separate from** the :file:`/project`
    mount point. This avoids volume mount ordering issues that can occur
    when nesting volumes.

..  _template-project-bundled:

Method 2: bundle templates in your project
==========================================

Place your custom templates in a directory named
:file:`resources/custom-templates` **at the root of your repository**:

..  code-block:: text

    my-project/
    |-- Documentation/
    |   |-- Index.rst
    |   +-- ...
    |-- resources/
    |   +-- custom-templates/
    |       +-- structure/
    |           +-- layout.html.twig
    +-- ...

When you mount your project into the container with
``-v "$(pwd):/project"``, the path
:file:`/project/resources/custom-templates` is detected automatically.
No extra volume mount is needed:

..  code-block:: shell

    docker run --rm \
      --pull always \
      -v "$(pwd):/project" \
      -it ghcr.io/typo3-documentation/render-guides:latest \
      --progress --config=Documentation

..  _template-directory-structure:

Directory structure for custom templates
========================================

Your custom templates must mirror the internal directory structure of the
built-in templates you want to override.

For example, if the original file is located at:

..  code-block:: text

    typo3-docs-theme/resources/template/structure/layout.html.twig

then your custom file must be placed at:

..  code-block:: text

    my-custom-templates/structure/layout.html.twig

When both files exist, your version is used instead of the original.
Any template you do **not** override continues to use the built-in
version.

..  _template-finding-originals:

Finding the original templates
==============================

To create a customized version of a template, first copy the original
from the container image.

Open a shell inside the container to browse all available templates:

..  code-block:: shell

    docker run --rm -it \
      --entrypoint=sh \
      ghcr.io/typo3-documentation/render-guides:latest

..  note::

    The ``--entrypoint=sh`` flag is required because the container's
    default entrypoint routes all commands to the PHP guides application.
    Without it, shell commands like ``cat`` or ``ls`` would be
    interpreted as guides subcommands.

Once inside, the templates are organized as follows:

..  list-table::
    :header-rows: 1
    :widths: 30 70

    *   -   **Theme / Purpose**
        -   **Location in container**
    *   -   TYPO3-specific templates
        -   :file:`/opt/guides/packages/typo3-docs-theme/resources/template/`
    *   -   Bootstrap 5 theme templates
        -   :file:`/opt/guides/vendor/phpdocumentor/guides-theme-bootstrap/resources/template/`
    *   -   reStructuredText (reST) templates
        -   :file:`/opt/guides/vendor/phpdocumentor/guides-restructured-text/resources/template/html/`
    *   -   Base templates (shared core)
        -   :file:`/opt/guides/vendor/phpdocumentor/guides/resources/template/html/`

..  _template-copying:

Copying a template from the container
-------------------------------------

To copy a specific template to your local machine, use
``--entrypoint=cat``:

..  code-block:: shell

    docker run --rm \
      --entrypoint=cat \
      ghcr.io/typo3-documentation/render-guides:latest \
      /opt/guides/packages/typo3-docs-theme/resources/template/structure/layout.html.twig \
      > my-custom-templates/structure/layout.html.twig

Make sure the target directory exists before running the command:

..  code-block:: shell

    mkdir -p my-custom-templates/structure

Edit the copied file locally. The next time you run the container with
your custom templates mounted, your modified version will automatically
be used.

..  _template-examples:

Examples
========

Override the page layout
------------------------

..  code-block:: shell

    mkdir -p my-custom-templates/structure

    docker run --rm \
      --entrypoint=cat \
      ghcr.io/typo3-documentation/render-guides:latest \
      /opt/guides/packages/typo3-docs-theme/resources/template/structure/layout.html.twig \
      > my-custom-templates/structure/layout.html.twig

    # Edit my-custom-templates/structure/layout.html.twig to your liking

    docker run --rm \
      -v "$(pwd):/project" \
      -v "$(pwd)/my-custom-templates:/templates:ro" \
      -it ghcr.io/typo3-documentation/render-guides:latest \
      --progress --config=Documentation

Override block quote rendering
------------------------------

..  code-block:: shell

    mkdir -p my-custom-templates/body

    docker run --rm \
      --entrypoint=cat \
      ghcr.io/typo3-documentation/render-guides:latest \
      /opt/guides/vendor/phpdocumentor/guides/resources/template/html/body/quote.html.twig \
      > my-custom-templates/body/quote.html.twig

    # Edit and render as above
