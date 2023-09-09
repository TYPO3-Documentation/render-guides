
=============
render-guides
=============

Render TYPO3 Documentation with `phpdocumentor/guides`.

Usage with Docker (locally)
===========================

::

    git clone git@github.com:TYPO3-Documentation/render-guides.git
    cd render-guides
    docker build --file Dockerfile --tag typo3-docs:local .
    docker run --rm --user=$(id -u):$(id -g) --volume ${PWD}:/project typo3-docs:local ./Documentation ./output --theme=typo3docs
    // output is now in folder "output"

Good to know for debugging::

    docker run --entrypoint=sh -it --rm typo3-docs:local

Let's you go in the shell so you can look around.



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

