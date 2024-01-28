..  include:: /Includes.rst.txt

..  _mono-repository:

===============
Mono-repo setup
===============

This repository is following a mono-repo setup. This means all code and
configuration to render documentation is in this repository. This includes
scripts to build the documentation and the configuration for the CI/CD pipeline.

Some packages in this repository can be used as standalone packages when not
rendering documentation for TYPO3, but for internal company documentation.

To ensure the mono-repo setup works, and also works in separate repositories,
we are using a tool called `monorepo-builder`_. This tool will help us to keep
the dependencies over packages in sync.

If you add a new dependency to a package, you can run

..  code-block:: shell

    make monorepo

This will update the root :file:`composer.json` file with the new dependency.

It is recommended to run the validation check before you commit:

..  code-block:: shell

    make test-monorepo

.. _`monorepo-builder`: https://github.com/symplify/monorepo-builder

..  _repository_split:

Repository split (subtree-split of this mono-repository)
========================================================

To be listed on `Packagist <https://packagist.org>`__ each package
in the folder :file:`packages/` of this mono-repository has to have its own
Git repository (called "subtree split"). On merging (push/commit) and
creating tags, these subrepositories are automatically updated by the GitHub
action :file:`.github/workflows/split-repositories.yaml`.

To add a new split-repository package, the following is needed:

*   Create a repository on GitHub with the matching composer name, special
    signs replaced by minus.
*   Configure the repository with :guilabel:`maintain` rights to the group
    `PHP-based-rendering-bot <https://github.com/orgs/TYPO3-Documentation/teams/php-based-rendering-bot>`__
*   Add an entry to file :file:`config.subsplit-publish.json`, formatted
    like the existing subtrees.
*   Push some change within the correspondig package directory on the
    mono-repository's `main` branch.
*   Add the new repository to `Packagist <https://packagist.org>`__.

..  note::
    Trigger a **push** first, before a release (tag) is made, to initialize
    the split-repo. Otherwise you will get an error "fatal: bad object type".
