..  include:: /Includes.rst.txt

..  _TableOfContentsJson:

===========================
Table of contents as JSON
===========================

Every manual is rendered with a :file:`toc.json` at its root: what pages there
are, in the order the table of contents puts them and nested the way it nests
them.

..  code-block:: json

    {
        "project": {
            "title": "TYPO3 Explained",
            "version": "main",
            "permalink": "https://docs.typo3.org/permalink/t3coreapi:{anchor}@main"
        },
        "pages": [
            {
                "path": "Index",
                "title": "TYPO3 Explained",
                "anchor": "api-overview",
                "pages": [
                    {
                        "path": "Introduction/Index",
                        "title": "Introduction",
                        "anchor": "introduction"
                    }
                ]
            },
            {
                "orphan": true,
                "path": "404",
                "title": "Content was removed",
                "anchor": "not-found"
            }
        ]
    }

What the project says is said once, at the top:

..  rst-class:: dl-parameters

title
    The title of the manual.

version
    The version it was rendered as, or :samp:`""` for a manual that names none.

permalink
    The permalink of every page at once: put a page's :samp:`anchor` where
    :samp:`{anchor}` is. :samp:`""` for a manual that declares no interlink
    shortcode and therefore has no permalinks.

Each page carries only what is its own:

..  rst-class:: dl-parameters

path
    The page within the manual, relative to this file and without an extension,
    because the same page exists as :samp:`.html` and as :samp:`.md` and a
    reader wants to choose. Works in a local render and under
    :samp:`docs.typo3.org` alike.

title
    The title of the page.

anchor
    The label the page is known by: its own if it declares one, otherwise the id
    derived from its title. Fill it into the project's :samp:`permalink`.

pages
    The pages this one's table of contents leads to, in its order. Left out
    where there are none.

orphan
    Present and :samp:`true` on a page no table of contents leads to. Listed all
    the same, because the file is published, and a table of contents that
    silently drops published pages is worse than one that says where they stand.

Why it exists
=============

A tool that wants to read a manual has to learn its shape first, and until now
nothing published said it. :file:`objects.inv.json` comes closest, but it is an
index rather than a table of contents: a flat map without order or nesting,
which repeats the project title and version in every one of its entries and
knows only :samp:`.html` addresses.

Saying those things once is most of the difference in size. For TYPO3 Explained,
982 pages, :file:`objects.inv.json` is 4.2 MB and :file:`toc.json` is 348 KB --
27 KB against 343 KB once the server has compressed them.

..  note::

    The file lists pages, not the headings inside them. Those are in
    :file:`objects.inv.json`, which stays the place to look up a single anchor.

See :ref:`ChangelogIndex` for the Core Changelog, which is rendered with an
index of its entries on top of this.
