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
    composer pre-commit-test

    # Using ddev
    ddev composer pre-commit-test


You can use a helper script to set this up once in your project, so that
these checks are performed before any GIT commit:

::

    # Using Makefile
    make githooks

    # Using composer
    composer make githooks

    # Using ddev
    ddev composer make githooks

Those git hooks will also check your commit message for line length.

