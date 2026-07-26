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

Merging pull requests
=====================

Pull requests into ``main`` are merged through a `merge queue
<https://docs.github.com/en/pull-requests/collaborating-with-pull-requests/incorporating-changes-from-a-pull-request/merging-a-pull-request-with-a-merge-queue>`__:
instead of merging directly, use :guilabel:`Merge when ready`. The queue
re-runs the required checks against the latest ``main`` before merging, so a
pull request whose checks were green against an older ``main`` cannot break
the branch. If the checks of a queued pull request fail, the pull request is
removed from the queue and only that pull request is blocked.

See `ADR-004
<https://github.com/TYPO3-Documentation/.github/blob/main/Documentation/Decisions/ADR-004-MergeQueues.rst>`__
for the decision and its scope.

