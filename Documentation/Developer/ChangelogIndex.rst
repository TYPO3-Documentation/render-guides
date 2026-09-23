..  include:: /Includes.rst.txt

..  _ChangelogIndex:

=======================
Changelog index as JSON
=======================

Every manual is rendered with a :ref:`table of contents as JSON
<TableOfContentsJson>`. The TYPO3 Core Changelog gets one artifact on top that
no other manual does: a machine-readable index of its entries, one file per
major release, written beside the overview page it belongs to.

:file:`Changelog-13.json` sits next to :file:`Changelog-13.html` and
:file:`Changelog-13.md` and lists the same entries the overview page lists:

..  code-block:: json

    {
        "major": 13,
        "entries": [
            {
                "permalink": "https://docs.typo3.org/permalink/changelog:deprecation-104764-1724851918",
                "path": "Changelog/13.3/Deprecation-104764-FluidTemplatePaths-fillDefaultsByPackageName",
                "html": "Changelog/13.3/Deprecation-104764-FluidTemplatePaths-fillDefaultsByPackageName.html",
                "md": "Changelog/13.3/Deprecation-104764-FluidTemplatePaths-fillDefaultsByPackageName.md",
                "title": "Deprecation: #104764 - Fluid TemplatePaths->fillDefaultsByPackageName",
                "anchor": "deprecation-104764-1724851918",
                "type": "deprecation",
                "issue": 104764,
                "typo3-version": "13.3",
                "typo3-major": 13,
                "tags": ["PHP-API", "FullyScanned", "ext:fluid"],
                "classes": {
                    "\\TYPO3\\CMS\\Core\\View\\ViewFactoryInterface": [
                        { "section": "migration", "kind": "inline" }
                    ],
                    "\\TYPO3\\CMS\\Fluid\\View\\TemplatePaths": [
                        { "section": "description", "kind": "inline", "members": ["->fillDefaultsByPackageName()"] }
                    ]
                }
            }
        ]
    }

..  rst-class:: dl-parameters

path
    The entry's file within the manual, without an extension.

html
    The entry's page, relative to this file: :samp:`path` with :samp:`.html`.
    Left out when the Changelog was not rendered to HTML page by page.

md
    The entry's Markdown, relative to this file: :samp:`path` with :samp:`.md`.
    Left out when the Changelog was not rendered to Markdown page by page.

permalink
    The URL that names the entry wherever its file ends up. The Changelog is
    deployed to :samp:`main` only, so these never carry an :samp:`@version`.

title
    The headline of the entry, for example
    :samp:`Feature: #105638 - Modify fetched page content`.

anchor
    The label the entry is known by: what the permalink resolves against, and
    what a link to the entry has to name.

type
    One of :samp:`feature`, :samp:`breaking`, :samp:`deprecation` or
    :samp:`important`.

issue
    The Forge issue the entry documents.

typo3-version
    The release the entry belongs to, read from its path, for example
    :samp:`13.4.x`. The document's own version is :samp:`main` for every entry
    and therefore says nothing.

typo3-major
    The major of that release, so a consumer can filter on it without parsing
    the version.

tags
    The terms of the entry's :rst:`.. index::` directive, such as
    :samp:`Frontend` or :samp:`ext:core`.

classes
    The PHP classes the entry speaks of, by their fully qualified name, each
    with the places in the entry: the :samp:`section`, :samp:`kind` and
    :samp:`members` of the :ref:`class index <ClassIndexJson>`, which says the
    same by class. A class under :samp:`migration` is often what replaces
    the one under :samp:`description` or :samp:`impact`. The page and release
    are the entry's; a place names an :samp:`anchor` only where it lies below
    a label of its own inside the entry. Left out for an entry that names no
    class.

Entries are ordered newest release first, matching the overview page, and
within a release by issue, so the file does not change between two renders of
the same sources.

Why it exists
=============

The overview page links its entries by permalink, which costs a redirect per
entry -- 445 of them for v14 -- for anybody walking a whole release rather than
reading one page. The index is that list in a single request, and it carries
what the page cannot: the file of each entry, so a reader can go straight to
the Markdown instead of scraping HTML.

The Changelog is recognised by its interlink shortcode, and the format is added
by the theme rather than configured in the manual: the Changelog's
:file:`guides.xml` lives in :samp:`typo3/cms-core`, and a documentation artifact
should not cost a Core patch.
