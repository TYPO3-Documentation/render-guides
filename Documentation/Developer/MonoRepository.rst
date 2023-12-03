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
