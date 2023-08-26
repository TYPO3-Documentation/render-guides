
=============
render-guides
=============

Render TYPO3 Documentation with `phpdocumentor/guides`.

Usage with DDEV:
================

::

    git clone git@github.com:TYPO3-Documentation/render-guides.git
    cd render-guides
    ddev start
    ddev composer install
    cp -R /path/to/my/docs/Documentation Documentation
    ddev composer render
    // output is now in folder "output"
    ddev launch Index.html

