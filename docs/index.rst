
=============
render-guides
=============

Mono-repo setup
===============

This repository is following a mono-repo setup. This means all code and
configuration to render documentation is in this repository. This includes
scripts to build the documentation, the configuration for the CI/CD pipeline.

To ensure the mono-repo setup works, and also works in separate repositories,
we are using a tool called monorepo-buider_. This tool will help us to keep the
dependencies over packages in sync.

If you add a new dependency to a package, you can run

::
    composer run monorepo:merge

This will update the root composer.json file with the new dependency.

We do recommend to run the validation check before you commit.

::
    composer run monorepo:validate

.. _monorepo-builder: https://github.com/symplify/monorepo-builder
