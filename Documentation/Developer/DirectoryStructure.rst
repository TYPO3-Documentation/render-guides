..  include:: /Includes.rst.txt

..  _directory-structure:

===================
Directory structure
===================

The directory structure of the project is as follows:

..  code-block:: plaintext

    .
    ├── .ddev
    ├── .github
    │   ├── workflows
    │   │   ├── check-built-assets.yaml
    │   │   ├── dependabot-rebuild-assets.yaml
    │   │   ├── deploy-azure-assets.yaml
    │   │   ├── docker-test.yaml
    │   │   ├── docker.yaml
    │   │   ├── main.yaml
    │   │   ├── pr-auto-approve.yaml
    │   │   ├── pr-auto-merge.yaml
    │   │   ├── split-repositories.yaml
    ├── bin
    ├── Documentation
    ├── packages
    │   ├── typo3-api
    │   ├── typo3-docs-theme
    │   ├── typo3-docs-theme-md
    │   ├── typo3-guides-cli
    │   ├── typo3-guides-extension
    │   ├── typo3-version-handling
    ├── tests
    │   ├── Integration
    ├── tools


The :file:`.ddev/` directory contains the configuration for the local development
environment for people using `DDEV`_.

The :file:`.github/` directory contains the configuration for the GitHub workflows.
The main workflow runs the quality checks for each pull request and each
push to the `main` branch. The Docker workflow builds the Docker image for
each release. The other workflows check the built theme assets, publish them,
split the packages into their own repositories, and handle Dependabot pull
requests.

The :file:`bin/` directory contains the executable :file:`bin/guides`. The
Docker image runs it, and the phar file starts from it. It validates the
:file:`guides.xml` and then starts phpDocumentor/guides.

The :file:`packages/` directory contains the TYPO3-specific extensions. The
``typo3-docs-theme`` extension contains the theme for the documentation, and
TYPO3-specific directives. The ``typo3-docs-theme-md`` package renders Markdown
beside the HTML. The ``typo3-guides-extension`` extension contains
extensions on the base tool. These are customizations to make the tool fit the
TYPO3 documentation needs. The ``typo3-guides-cli`` package is a collection
of Symfony commands for tasks like conversion or linting. The
``typo3-version-handling`` package knows the TYPO3 versions and the default
interlink inventories. The ``typo3-api`` package generates api.typo3.org.

The :file:`tests/` directory contains the tests for the project. The integration
tests are located in the :file:`Integration/` directory. During the integration
tests the tool is executed as it was run by the user.

The :file:`tools/` directory contains the tools needed for the project. Like
scripts to build the phar file or adapt Git hooks.

The :file:`Documentation/` directory holds the documentation you are now
reading. It is the default documentation built by the project, too.

The :file:`Dockerfile` and :file:`entrypoint.sh` files provide the Docker
container support for the project.

The :file:`Makefile` contains a list of all supported development commands and
tasks that can be executed. All of them can also be executed with a Composer
wrapper script (:bash:`composer make ...`).

..  _DDEV: https://ddev.com/
