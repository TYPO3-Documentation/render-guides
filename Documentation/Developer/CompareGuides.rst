..  include:: /Includes.rst.txt

..  _compare-guides:

=====================================
Testing a phpDocumentor/guides change
=====================================

The TYPO3 theme extends `phpDocumentor/guides`_ with its own directives,
nodes, and templates. A change in guides can change how TYPO3 manuals
render, even when the tests of guides pass. The command
:bash:`make compare-guides` shows these changes before a guides release
reaches docs.typo3.org.

The command renders real TYPO3 manuals twice with this repository:

*   with guides at the point where your branch started, the *base*
*   with the working tree of your guides checkout, the *head*, including
    uncommitted changes

Then it lists each file that differs between the two renders.

..  _compare-guides-requirements:

Requirements
============

*   Git, Docker, and `make`
*   A checkout of this repository
*   A checkout of phpDocumentor/guides with the change that you want to test

..  _compare-guides-usage:

Usage
=====

Run the command in this repository. Give it the path of your guides
checkout:

..  code-block:: shell

    git clone https://github.com/TYPO3-Documentation/render-guides.git
    cd render-guides
    make compare-guides GUIDES=../guides

The first run installs the Composer dependencies and clones the manuals. The
manuals use sparse clones that contain only the documentation. Later runs
update the clones. With the default manuals, a run took about two minutes in
a test, and about six minutes with all manuals.

The command prints the result for each manual:

..  code-block:: text

    manual                 result
    guide-h2d              4 of 359 files differ
                           .cache/compare-guides/report/guide-h2d.diff
    reference-tca          identical (397 files)

A report shows the differences in all files of a manual as a unified diff.
The rendered output of both versions is in
:file:`.cache/compare-guides/output/base/` and
:file:`.cache/compare-guides/output/head/`. Open a page in a browser to see
how a difference looks.

A difference is not always an error. Look at each one, and decide whether
your change intends it.

..  _compare-guides-options:

Options
=======

GUIDES
    The path of the phpDocumentor/guides checkout. This option is required.

BASE
    The branch that your change goes into. The default is
    `origin/main`. The base is the commit where your branch left this
    branch, so changes on it since then do not show up as differences. If the
    remote of phpDocumentor/guides has another name in your checkout, set
    :bash:`BASE=upstream/main`, for example.

    A branch that is merged into `BASE` already has no changes of its own,
    and the command stops. To see what a merged change did, set `BASE` to
    the commit before it, such as :bash:`BASE=61eeb756^`.

MANUALS
    The manuals to render, separated by spaces. The default renders the
    How to Document guide, the TCA reference, the TypoScript reference, the
    ViewHelper reference, and TYPO3 Explained. :bash:`MANUALS=all` adds the
    Changelog and the exception reference, which take the longest:

    ..  code-block:: shell

        make compare-guides GUIDES=../guides MANUALS=all
        make compare-guides GUIDES=../guides MANUALS="changelog coreapi"

    The list of manuals is at the start of :file:`tools/compare-guides.sh`.

ENV
    :bash:`ENV=local` renders with the PHP of your host instead of Docker.

..  _compare-guides-limits:

Limits
======

*   The command replaces the code of the guides packages in a copy of
    :file:`vendor/`. It does not run Composer for your branch. If your change
    adds a dependency, the command warns, and a render can fail because the
    dependency is missing.
*   With Docker, the renders have no network access. Links to other manuals
    and to api.typo3.org stay unresolved. They stay unresolved in both
    renders, so they do not show up as differences. With :bash:`ENV=local`,
    both renders use the network, and an answer that changes between the
    renders shows up as a difference.
*   The command compares the HTML and Markdown output, and the warnings and
    errors of the render. It does not check whether a page looks right.
*   After a run with all manuals, :file:`.cache/compare-guides/` takes about
    1 GB. Delete the directory when you no longer need it. The next run
    clones the manuals again.

..  _phpDocumentor/guides: https://github.com/phpDocumentor/guides
