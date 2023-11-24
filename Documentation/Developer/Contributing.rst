..  include:: /Includes.rst.txt

..  _Contributing:

============
Contributing
============

When contributing please run all tests before committing:

::

    # Using Makefile (append ENV=local if you don't require docker)
    make pre-commit-test

    # Using composer
    composer make pre-commit-test

    # Using ddev
    ddev composer make pre-commit-test


You can use a helper script to set this up once in your project, so that
these checks are performed before any Git commit:

::

    # Using Makefile
    make githooks

    # Using composer
    composer make githooks

    # Using ddev
    ddev composer make githooks

Those Git hooks will also check your commit message for line length.

