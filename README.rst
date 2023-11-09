
=============
render-guides
=============

Render TYPO3 Documentation with `phpdocumentor/guides`.

Usage with Docker
=================

::
    mkdir Documentation-GENERATED-temp
    docker run --rm --pull always -v $(pwd):/project -it ghcr.io/typo3-documentation/render-guides:main --output Documentation-GENERATED-temp Documentation

Contributing
============

When contributing please run all tests before committing::

    make pre-commit-test

You can use a helper script to set this up once in your project::

    make githooks

Those git hooks will also check your commit message for line length.

Both commands utilize the Makefile syntax and docker.

Usage with Docker (locally)
===========================

::

    git clone git@github.com:TYPO3-Documentation/render-guides.git
    cd render-guides
    docker build --file Dockerfile --tag typo3-docs:local .
    docker run --rm --user=$(id -u):$(id -g) --volume ${PWD}:/project typo3-docs:local ./Documentation ./Documentation-GENERATED-temp --theme=typo3docs
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
    cp -R /path/to/my/docs/Documentation fixtures-local
    ddev composer render
    // output is now in folder "output"
    ddev launch Index.html

