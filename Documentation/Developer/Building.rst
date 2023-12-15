..  include:: ../Includes.rst.txt

..  _Building:

========
Building
========

While the components of this repository are split into separate packages, it can
be build as a standalone application. Two ways are provided to use this project
on your own environment.

Using Docker
------------

A Docker image is available on `GitHub packages`_. If you want to build your own
image you can use the following command, in the root of this repository.

..  code-block:: shell

    make docker-build

Once the build is finished you can execute your own image using:

..  code-block:: shell

    docker run --rm -v $(pwd):/project typo3-docs:local --progress

For macOS you may need to specify the argument ``user``:

..  code-block:: shell

    docker run --rm -v $(pwd):/project --user=$(id -u):$(id -g) typo3-docs:local --progress

Using PHP
---------

A phar_ binary is shipped with this repository. In short, a phar file is an
executable PHP file. You can run it like any other executable.

To build the phar file we use Box_, with some wrapper script. To build the phar
file yourself, you can run the following command.

..  code-block:: shell

    make build-phar

This will create a file called :file:`guides.phar` in the build directory. You
can execute the phar file like a PHP file using:

..  code-block:: shell

    php build/guides.phar

..  warning::

    Currently, Box is not able to build a phar file for projects containing
    Composer plugins as it will only install production dependencies. This
    means that the :file:`composer.json` is modified during the build process.
    You shall not commit this change to the repository.

GitHub workflow
---------------

Developers contributing to the repository of this project on github_renderguides_
will trigger several GitHub Workflow when commiting/pushing code.

On terminology: A `GitHub Workflow` is something that is triggered within
the repository (i.e. on a commit/tag). A `GitHub Action` is something that can
be executed from the repository, where the repository allows other repositories
to i.e. render the documentation. This repository provides both `Workflows` and
`Actions`.

GitHub workflow: Commit/PR stragegy
-----------------------------------

The GIT `main` branch is protected, so only feature/bugfix/task-branches and forked
repositories can be merged into it. When a Pull Request (PR) is created, at least
one team member needs to approve it, so that it can be merged.

When a PR is created (and for every follow-up commit) the following GitHub Action
are executed:

-   :file:`.github/workflows/main.yaml` runs code quality and integration checks
-   :file:`.github/workflows/docker-self.yaml` creates a test docker container image
    (not uploaded anywhere), only if the PR modifies the main :file:`Dockerfile`.

Once a PR is merged, nothing else happens.

GitHub workflow: Release strategy
-----------------------------------

Whenever assets of our theme (`packages/typo3-docs-theme`) need to be uploaded,
or the official Docker container needs to be updated, a GIT tag must be pushed to
this repository.

The tag must be formatted as a semver-version string without a leading character,
i.e. `0.1.0` or `5.1.1`. We only support a progressive mainline of versions, so
if a `5.0.0` version will come out at some point, backporting bugfixes to previous
major versions is not planned.

If that ever needs to happen, also tags for older versions can be added to GIT
to trigger building the relevant Docker container images. It is then very important
that the most recent version is tagged LAST in this process, because only the
last GIT tag is used for the `latest` Docker container:

..  code-block:: shell

    # DO this:
    git checkout 3.1.2
    # .. cherry-pick bugfixes ..
    # .. commit to a branch like release/3.1.3 ..
    git tag 3.1.3 && git push --tags
    git checkout main
    # .. release the main version
    git tag 4.0.0 && git push --tags

    # do NOT do this:
    git tag 4.0.0 && git push --tags
    git checkout 3.1.2
    # ...

When a GIT version tag matching `*.*.*` is pushed, these workflows are executed:

-   :file:`.github/workflows/phar.yaml` build the PHAR image for the release
-   :file:`.github/workflows/docker.yaml` build the Docker container image for the release,
    using the version tag.
-   :file:`.github/workflows/deploy-azure-assets.yaml` uploads the latest assets (everything in
    :file:`packages/typo3-docs-theme/resources/public`) to the Azure cloud CDN,
    using the version tag.

GitHub workflow: GitHub Actions - Main entry
--------------------------------------------

:file:`/action.yaml` is the main entry point for a composite action. It can
be used by other repositories in workflows.

The GitHub repository github_gh_render_action_ provides an easy interface to
that action. That repository provides a wrapper to check if a documentation repository
needs to be rendered using Sphinx (the old rendering, using a :file:`Settings.cfg`
file) or via phpDocumentor (the new rendering, using a : file:`guides.xml` file).

The central piece of that action is:

..  code-block:: shell

    - uses: TYPO3-Documentation/render-guides@main
      id: render-guides
      if: steps.enable_guides.outputs.GUIDES == 'true'
      with:
        working-directory: t3docsproject
        config: ./t3docsproject/Documentation/
        output: ./RenderedDocumentation/Result/project/0.0.0
        configure-branch: ${{ env.source_branch }}
        configure-project-release: ${{ env.target_branch_directory }}
        configure-project-version: ${{ env.target_branch_directory }}

This "remotely executes" the :file:`/action.yaml` of this repository with specific
input parameters gathered earlier in the `gh-render-action` action.

All of this allows an extension author to provide a GitHub Workflow in their own
repository like this:

..  code-block:: shell

    jobs:
      render-documentation:
        runs-on: ubuntu-latest
        name: "Render Documentation for this repository and upload"
        steps:
          - name: Render Repository
            uses: TYPO3-Documentation/gh-render-action@main
            id: rendering
            with:
              repository_url: https://github.com/$GITHUB_REPOSITORY
              source_branch: main
              target_branch_directory: main

Then it does not even matter, if the repository uses the old or new rendering,
everything is done through the intermediate layer of `gh-render-action`.

This will also in the future allow us to switch to different renderings or take
care of breaking configurations, so that extension authors (and the TYPO3 core
documentation) always can rely on one action that does not change, and does
not need different version numbers/tags.

The :file:`/action.yaml` composite action takes in the input of the code snippet
above, and then executes two composite steps:

-    :file:`.github/actions/configure-guides-step/action.yaml` that provides
     extension-repository specific attributes that influence the local rendering.
     The input variables are dynamically injected into a temporary :file:`guides.xml`
     file, that is used for the actual rendering. This is done by executing
     our own `latest` Docker container image.
-    :file:`.github/actions/render-guides-step/action.yaml` is the actual
     rendering step, also using the same `latest` Docker container image.

Note that we only have one central Docker container image entrypoint that can
take arguments like `migrate` or `render` to trigger different actions.


GitHub workflow: deploy-azure-assets
------------------------------------

:file:`.github/workflows/deploy-azure-assets.yaml` is triggered when a GIT tag
matching `*.*.*` is committed to the repository.

It checks out this repository, retrieves the current GIT tag, gathers all files in
:file:`packages/typo3-docs-theme/resources/public` and moves them to a directory
structure like :file:`cdn/cdn/theme/typo3-docs-theme/1.0.0/` (using the version
number that has been used in the GIT tag).

That directory structure is then uploaded to azure, by using the secret GIT
environment variables configured in our repository.

GitHub workflow: docker
-----------------------

:file:`.github/workflows/docker.yaml` is triggered when a GIT tag
matching `*.*.*` is committed to the repository.

It does these steps:

-   `build`: Sets up a matrix of docker platforms (arm, amd) to be built. This
    results in three build steps in total, once per platform:
    -   Check out the repository with the current GIT tag
    -   Retrieve Docker metadata (tags, versions)
    -   Initiates the Docker build chain
    -   Store the currently used GIT tag (version number) in an environment
        variable `TYPO3AZUREEDGEURIVERSION`. See description below.
    -   Create the docker image, using the environment variable.
-   `merge`: Then the three builds are merged and uploaded to the `gchr` docker
    registry.

The variable `TYPO3AZUREEDGEURIVERSION` is very important to be baked into
the Docker image. This will ensure, that the rendering for remote repositories
is always performed with the matching version number of both the theme and the
Docker image. All assets can then be referenced as
`https://typo3.azureedge.net/typo3documentation/theme/typo3-docs-theme/$TYPO3AZUREEDGEURIVERSION/img/typo3-logo.svg`.

Note that the version is used here, not a string like `main` or `stable` as the
version, because CDNs would always cache these files and probably not deliver a
new version, because the URI would be the same.

This means, the `latest` Docker image container will always reference to the CDN
with the most recent version number. If at some point incompatibilities in the
rendering are introduced, we can separate the `gh-render-action` repository in
a way, that could reference exact Docker images other than `latest`, like by
referring to a `:5.0` image (using 5.0.1 / 5.0.2 / ... CDN URIs), or even using
`:5` to reference to the most recent 5.x version.

GitHub workflow: docker-test
----------------------------

:file:`.github/workflows/docker-test.yaml` is triggered whenever a commit changes
the :file:`Dockerfile`.

The workflow step then uses that modified :file:`Dockerfile` and tries to build
it, and just execute the resulting container.

Note that no Docker container is actually uploaded. All the GitHub actions
are just executed with the locally built docker container in this case, because
this workflow step replaces the Docker container image name to the local Dockerfile
instead of a registry URI.

A limitation currently is that the local Docker image will always use the action
steps to configure and render the documentation from the `main` repository, not
the fles that may be modified within the commit. See the note in
:file:`.github/workflows/docker-test.yaml` at the end for details.

GitHub workflow: main
-----------------------

:file:`.github/workflows/main.yaml` is triggered on each commit and for each
Pull Request (PR).

It performs basic code quality analysis and execution of unit/integration tests:

-   Tests:

    -   `Run unit tests`

    -   `Run integration tests`

-   Quality:

    -   `CGL`

    -   `PHPSTAN`

    -   `Lint guides.xml configurations`

-   Validate monorepo structure

GitHub workflow: phar
-----------------------

(work in progress)

:file:`.github/workflows/phar.yaml` is triggered on each commit (not on
Pull Requests). It builds the a phar_ archive that will be available for
created releases.


.. _phar: https://www.php.net/manual/en/intro.phar.php
.. _Box: https://box-project.github.io/box/
.. _`GitHub packages`: https://github.com/TYPO3-Documentation/render-guides/pkgs/container/render-guides
.. _github_renderguides: https://github.com/TYPO3-documentation/render-guides
.. _github_gh_render_action: https://github.com/TYPO3-documentation/gh-render-action
