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

With make
=========

..  code-block:: sh

    make migrate-settings path=path/to/Documentation

With ddev
=========

..  code-block:: sh

    ddev exec ddev composer make migrate-settings path=path/to/Documentation
