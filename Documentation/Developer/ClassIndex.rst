..  include:: /Includes.rst.txt

..  _ClassIndexJson:

=====================
Class index as JSON
=====================

Every manual is rendered with a :file:`classes.json` beside its
:ref:`toc.json <TableOfContentsJson>`: every PHP class the manual speaks of,
and where it does so.

It answers what no rendered file answered before: where a class is explained,
and what else says anything about it. :file:`objects.inv.json` lists what a
manual documents, which is the definitions alone.

..  code-block:: json

    {
        "project": {
            "title": "TYPO3 Explained",
            "version": "main",
            "permalink": "https://docs.typo3.org/permalink/t3coreapi:{anchor}@main"
        },
        "classes": {
            "\\TYPO3\\CMS\\Core\\Utility\\GeneralUtility": {
                "type": "class",
                "places": [
                    {
                        "path": "ApiOverview/Bootstrapping/Index",
                        "anchor": "bootstrapping-instantiation",
                        "kind": "role",
                        "member": "::makeInstance()"
                    },
                    {
                        "path": "ApiOverview/Bootstrapping/Index",
                        "anchor": "bootstrapping-instantiation",
                        "kind": "use"
                    }
                ]
            }
        }
    }

The project is described the way :file:`toc.json` describes it, and the
:samp:`permalink` there works for the anchors here too.

Each class is named in full, with a leading backslash, and carries what it is
and the places that speak of it:

..  rst-class:: dl-parameters

type
    What the TYPO3 API says the name is: :samp:`class`, :samp:`interface`,
    :samp:`trait` or :samp:`enum`. Left out for a name the API does not know --
    a class of an extension or of another vendor, but below
    :samp:`\\TYPO3` also a namespace, a typo or an invented example. Nothing
    in the writing tells those apart: a :samp:`use` statement imports a
    namespace and a class alike.

places
    Where the manual speaks of the class, ordered by page and anchor. Each
    place says:

..  rst-class:: dl-parameters

path
    The page within the manual, relative to this file and without an
    extension, as in :file:`toc.json`.

anchor
    The label of the section the class is spoken of in, so that a link leads to
    the passage rather than to the top of the page. Fill it into the project's
    :samp:`permalink`. For a :samp:`definition` it is the anchor of the class
    itself.

kind
    What the place is:

    ..  rst-class:: dl-parameters

    role
        The class is named in the text, with :rst:`:php:` or
        :rst:`:php-short:`.

    use
        A PHP example imports the class with a :samp:`use` statement. What the
        example then does with it is not counted again.

    definition
        The manual documents the class itself, with
        :rst:`.. php:class::` or one of its siblings.

member
    The member the text names, as it is written after the class:
    :samp:`::makeInstance()`, :samp:`->getAttribute()`, :samp:`::CONSTANT`.
    Left out where the text names the class alone, and only ever present on a
    :samp:`role`.

A class named twice in the same section is one place: an author repeating a
name does not make it a second mention.

A manual interested in one part of the world keeps that part alone, and names
the namespaces it wants. Everything below them is indexed, everything else is
passed over:

..  code-block:: xml

    <extension class="\T3Docs\Typo3DocsTheme\DependencyInjection\Typo3DocsThemeExtension"
               indexed-namespaces="TYPO3\CMS, TYPO3Fluid"
    />

Unset, which is the default, the index keeps every class a manual speaks of.

In a manual about the past, a missing :samp:`type` mostly means the class is
gone rather than misspelled: the API knows the current version, and the
:ref:`Changelog <ChangelogIndex>` speaks of 855 classes that no longer
exist. Which release an entry belongs to is in its :samp:`path`, as in
:samp:`Changelog/12.1/Deprecation-98996-...`.

Classes below :samp:`Vendor`, :samp:`MyVendor`, :samp:`Foo` or
:samp:`OriginalVendor` are left out: an example invents them for the reader to
replace with their own, so where they are spoken of says nothing. A manual
that invents other names says so on the extension, and names all of them --
the setting replaces the list above rather than adding to it:

..  code-block:: xml

    <extension class="\T3Docs\Typo3DocsTheme\DependencyInjection\Typo3DocsThemeExtension"
               example-vendors="Vendor, MyVendor, Acme"
    />

For TYPO3 Explained the file is about 750 KB, with 985 classes in 3083
places.
