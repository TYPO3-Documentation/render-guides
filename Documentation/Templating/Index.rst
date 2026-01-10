..  include:: /Includes.rst.txt

..  _override-templates:

====================
Overriding Templates
====================

By default, the `render-guides` container uses the Twig templates shipped
with the official TYPO3 Documentation theme. However, you can provide your own
templates to customize or extend the rendering output.

This can be done **without modifying the image**, simply by mounting a local
directory that contains your Twig template overrides.

..  important::

    Custom templates are only supported when you build the documentation
    **locally** (for example using Docker or DDEV) or within your **own CI/CD
    pipeline**.

    When your project documentation is built and deployed automatically via the
    **official TYPO3 documentation workflow** (to
    `https://docs.typo3.org <https://docs.typo3.org>`_), custom templates are
    **not supported**.

    The central rendering service uses a fixed, controlled version of the
    official theme to ensure consistency across all published manuals.

Using Docker to mount a volume with custom templates
====================================================

When using the official container image, you can mount your custom templates
directory into the container at the path :file:`/project/custom-templates`.

For example, if your custom templates are stored in a folder named
:file:`my-custom-templates` in your current working directory, run:

..  code-block:: shell

    docker run --rm \
      --pull always \
      -v "$(pwd):/project" \
      -v "$(pwd)/my-custom-templates:/project/custom-templates:ro" \
      -it ghcr.io/typo3-documentation/render-guides:latest \
      --progress --config=Documentation

The container automatically detects the mounted directory and prepends it to
the template search paths. You can confirm this by checking the container log
output (when verbose mode is enabled).

..  tip::

    You can also use a project-bundled directory named
    :file:`resources/custom-templates` inside your repository.
    If it exists, it will automatically be included as well.

Directory structure for custom templates
========================================

Your custom templates must mirror the internal structure of the built-in
templates that you want to override.
For example, if the original file is located at:

..  code-block:: text

    typo3-docs-theme/resources/template/structure/layout.html.twig

then your custom file must be placed at:

..  code-block:: text

    my-custom-templates/structure/layout.html.twig

When both files exist, your version will be used instead of the original.

Finding the Original Templates
==============================

To create a customized version, you can copy any of the existing Twig templates
from the image or from your local installation.

Inside the running container, templates are located under
:file:`/opt/guides/resources/template`.
You can inspect or copy them using the following command:

..  code-block:: shell

    # Example: copy the "singlepage" layout to your custom directory
    docker run --rm -it ghcr.io/typo3-documentation/render-guides:latest \
      cat /opt/guides/resources/template/structure/singlepage.html.twig \
      > my-custom-templates/structure/singlepage.html.twig

Then edit the file locally. The next time you run the container, your modified
version will automatically be applied.

Finding the Original Templates
------------------------------

To create a customized version, you can copy any of the existing Twig templates
from the image or from your local installation.

You can open a shell in the container to inspect all available templates:

..  code-block:: shell

    docker run --rm -it --entrypoint=sh ghcr.io/typo3-documentation/render-guides:latest

Once inside, you can browse the template directories.
They are organized as follows:

..  list-table::
    :header-rows: 1
    :widths: 30 70

    * - **Theme / Purpose**
      - **Location in Container**
    * - TYPO3-specific templates
      - `/opt/guides/packages/typo3-docs-theme/resources/template/`
    * - Bootstrap 5 theme templates
      - `/opt/guides/vendor/phpdocumentor/guides-theme-bootstrap/resources/template/`
    * - reStructuredText (reST) templates
      - `/opt/guides/vendor/phpdocumentor/guides-restructured-text/resources/template/html/`
    * - Base templates (shared core)
      - `/opt/guides/vendor/phpdocumentor/guides/resources/template/html/`

Example – Copying an Existing Template
--------------------------------------

If you want to customize the basic page layout `layout.html.twig` layout used
by the TYPO3 theme, copy it from the container into your local project and
modify it:

..  code-block:: shell

    docker run --rm -it ghcr.io/typo3-documentation/render-guides:latest \
      cat /opt/guides/packages/typo3-docs-theme/resources/template/structure/layout.html.twig \
      > my-custom-templates/structure/layout.html.twig

Then edit the file locally. The next time you run the container, your modified
version will automatically be applied.

If you want to change the output of the the block quotes, you need to override the
basic template:

..  code-block:: shell

    docker run --rm -it ghcr.io/typo3-documentation/render-guides:latest \
      cat /opt/guides/vendor/phpdocumentor/guides/resources/template/html/body/quote.html.twig \
      > my-custom-templates/body/quote.html.twig
