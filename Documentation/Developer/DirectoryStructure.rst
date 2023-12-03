..  include:: /Includes.rst.txt

..  _directory_structure:

===================
Directory structure
===================

The directory structure of the project is as follows:

..  code-block:: plaintext

    .
    ├── .ddev
    ├── .github
    │   ├── workflows
    │   │   ├── main.yml
    │   │   ├── phar.yml
    │   │   ├── docker.yml
    ├── bin
    ├── Documentation
    ├── packages
    │   ├── typo3-docs-theme
    │   ├── typo3-guides-extension
    ├── tests
    │   ├── Integration
    ├── tools


The :file:`.ddev/` directory contains the configuration for the local development
environment for people using `DDEV`_.

The :file:`.github/` directory contains the configuration for the GitHub workflows.
They are triggered by pull requests and pushes to the main branch. The main workflow
contains the quality assurance steps. The phar workflow builds the phar file on
release and nightly builds. Same applies for the Docker workflow on the docker side.

The :file:`bin/` directory contains the executable scripts for the project. The
file in there is needed for the phar build.

The :file:`packages/` directory contains the TYPO3-specific extensions. The
``typo3-docs-theme`` extension contains the theme for the documentation, and
TYPO3-specific directives. The ``typo3-guides-extension`` extension contains
extensions on the base tool. This are customizations to make the tool fit the
TYPO3 documentation needs.

The :file:`tests/` directory contains the tests for the project. The integration
tests are located in the :file:`Integration/` directory. During the integration
tests the tool is executed as it was run by the user.

The :file:`tools/` directory contains the tools needed for the project. Like
scripts to build the phar file, or the Docker image.

The :file:`Documentation/` directory holds the documentation you are now
reading. It is the default documentation being build by the project, too.


..  _DDEV: https://ddev.com/
