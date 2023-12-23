..  include:: ../Includes.rst.txt

..  _migration:

=========
Migration
=========

If you want to migrate from using Sphinx to render TYPO3 documentation
using the render-guides, the file :file:`Documentation/Settings.cfg` has to be
replaced by an XML file, :file:`Documentation/guides.xml`.

To facilitate migration the extension `t3docs/typo3-guides-cli` in this
mono-repository comes with a Symfony console command to automatically migrate
the outdated :file:`Settings.cfg`.

After migration, you can add your :file:`guides.xml` file to your custom repository,
and optionally also remove the old :file:`Settings.cfg` file.

With official Docker container
==============================

..  note::

    Work in progress - Note that we will use `:stable` at a later point to always provide stable
    images with a locked dependency and theme asset set.

To migrate your settings to the new rendering method, run the following
command in your project's root folder:

..  code-block:: shell

    docker run --rm --pull always -v $(pwd):/project -it ghcr.io/typo3-documentation/render-guides:latest migrate Documentation

The last option is the folder (here: :file:`Documentation`). When running the
command from another folder than the project's root folder, adapt the given
path accordingly.

With make
=========

To migrate your settings to the new rendering method, run the following
command in your project's root folder:

..  code-block:: shell

    make migrate-settings path=Documentation

When running the command from another folder than the project's root folder,
adapt the given path accordingly.

With ddev
=========

To migrate your settings to the new rendering method, run the following
command in your project's root folder:

..  code-block:: shell

    ddev composer make migrate-settings path=Documentation

When running the command from another folder than the project's root folder,
adapt the given path accordingly.

With PHP
========

To migrate your settings to the new rendering method, run the following
command in your project's root folder:

..  code-block:: shell

    packages/typo3-guides-cli/bin/typo3-guides migrate Documentation

The last option is the folder (here: :file:`Documentation`). When running the
command from another folder than the project's root folder, adapt the given
path accordingly.
