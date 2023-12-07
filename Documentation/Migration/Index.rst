..  include:: ../Includes.rst.txt

..  _migration:

=========
Migration
=========

If you want to migrate from using Sphinx to render TYPO3 documentation to
using the render-guides, the file :file:`Documentation/Settings.cfg` has to be
replaced by an XML file, :file:`Documentation/guides.xml`.

To facilitate migration the extension `t3docs/typo3-guides-cli` in this
mono-repository comes with a Symfony console command to automatically migrate
the outdated :file:`Settings.cfg`.

After migration, you can add your :file:`guides.xml` file to your custom repository,
and optionally also remove the old :file:`Settings.cfg` file.

With official Docker container
==============================

(Work in progress)

..  code-block:: sh

    docker run --rm --pull always -v $(pwd):/project -it ghcr.io/typo3-documentation/render-guides:main migrate

With make
=========

..  code-block:: sh

    make migrate-settings path=path/to/Documentation

With ddev
=========

..  code-block:: sh

    ddev composer make migrate-settings path=path/to/Documentation

With PHP
========

..  code-block:: sh

    packages/typo3-guides-cli/bin/typo3-guides migrate path/to/Documentation
