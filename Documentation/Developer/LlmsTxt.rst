..  include:: /Includes.rst.txt

..  _LlmsTxt:

====================
llms.txt of a manual
====================

Every manual is rendered with an :file:`llms.txt` at its root, following the
`llms.txt convention <https://llmstxt.org/>`__: where a language model starts
reading the manual. It is Markdown, and names what a client could not find
without knowing the names of our files:

..  code-block:: markdown

    # TCA Reference

    > Version main of this manual.
    > Every page is also available as Markdown: the same path with .md instead of .html.
    > The permalink of an anchor is https://docs.typo3.org/permalink/t3tca:{anchor}@main.

    ## Indexes

    - [toc.json](toc.json): Table of contents: every page in order, with its title and permalink anchor
    - [classes.json](classes.json): Every PHP class the manual speaks of, and where
    - [objects.inv.json](objects.inv.json): Every link target, as the interlinks of other manuals use them

    ## Pages

    - [TCA Reference](Index.md)
      - [Introduction](Introduction/Index.md)
      …

Only the indexes the render wrote are listed: a manual without options names
no :file:`confvals.json`. The pages are those its table of contents leads to,
nested as there, and linked as Markdown where the manual was rendered to
Markdown.

A manual with a :file:`404` page names it under "Removed content": the
official manuals keep there what was removed, by TYPO3 version, with what
replaces it, and old anchors and permalinks lead there.

The Core Changelog lists its pages down to each version, not its thousands of
entries. Those are in :ref:`Changelog-\<major\>.json <ChangelogIndex>`, with
their type, issue, tags and version to filter by, and each links its page.

The docs homepage writes none: the :file:`llms.txt` of docs.typo3.org is
written by hand.
