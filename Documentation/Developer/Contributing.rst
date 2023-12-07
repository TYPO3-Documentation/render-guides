..  include:: /Includes.rst.txt

..  _Contributing:

============
Contributing
============

When contributing please run all tests before committing:

..  code-block:: shell

    # Using Makefile (append ENV=local if you don't utilize docker)
    make pre-commit-test

    # Using composer (also uses "make" command internally, your OS may need a
    # package like "build-essential")
    composer make pre-commit-test

    # Using ddev
    ddev composer make pre-commit-test


You can use a helper script to set this up once in your project, so that
these checks are performed before any Git commit:

..  code-block:: shell

    # Using Makefile
    make githooks

    # Using composer
    composer make githooks

    # Using ddev
    ddev composer make githooks

Those Git hooks will also check your commit message for line length.

