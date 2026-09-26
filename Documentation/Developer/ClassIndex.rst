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
            "\\TYPO3\\CMS\\Core\\Authentication\\AbstractAuthenticationService": {
                "type": "class",
                "places": [
                    {
                        "path": "ApiOverview/Authentication/AuthenticationService/Index",
                        "anchor": "authentication-api",
                        "kind": "inline",
                        "members": ["::initAuth()"]
                    },
                    {
                        "path": "ApiOverview/Backend/BackendModules/SudoMode",
                        "anchor": "backend-module-sudo-extensions",
                        "kind": "code"
                    },
                    {
                        "path": "ApiOverview/Services/Developer/ServiceApi",
                        "anchor": "services-developer-service-api",
                        "kind": "inline"
                    }
                ]
            }
        }
    }

The project is described the way :file:`toc.json` describes it, and the
:samp:`permalink` there works for every anchor here.

Each class is named in full, with a leading backslash, and carries what it is
and the places that speak of it:

..  rst-class:: dl-parameters

type
    What the TYPO3 API says the name is: :samp:`class`, :samp:`interface`,
    :samp:`trait` or :samp:`enum`. Left out for a name the API does not know --
    a class of an extension or of another vendor, but below
    :samp:`\\TYPO3` also a typo, a class that is gone, or an invented example.

    A namespace is not a class and is not listed: one named with
    :rst:`:php-namespace:`, and one the API knows, named with :rst:`:php:` or
    imported by a :samp:`use` statement. Only a name that is a class and a
    namespace at once, like :samp:`\\TYPO3\\CMS\\Core\\Exception`, stays a
    class unless :rst:`:php-namespace:` says otherwise.

places
    Where the manual speaks of the class, ordered by page, anchor and section.
    Each place says:

..  rst-class:: dl-parameters

path
    The page within the manual, relative to this file and without an
    extension, as in :file:`toc.json`.

typo3-version
    In the :ref:`Changelog <ChangelogIndex>` only: the release of the entry the
    place is in, read from its path, for example :samp:`12.1` or
    :samp:`13.4.x`. Left out in every other manual.

anchor
    The nearest label at or above the place: the label of its section, or
    of the section or page around it. Fill it into the project's
    :samp:`permalink` to link to the passage rather than to the top of the
    page -- every anchor here resolves. Empty where the page has no label at
    all. For a :samp:`definition` it is the anchor of the class itself.

section
    The section the place is in, where that section has no label of its own:
    the id derived from its title, such as :samp:`migration` or
    :samp:`impact` in a Changelog entry. It works as a fragment on the page,
    :samp:`path` with :samp:`.html#` and the section, but not in a permalink.
    Left out where the section has a label, which is then the
    :samp:`anchor`.

kind
    Where the class stands:

    ..  rst-class:: dl-parameters

    inline
        In the text, named with :rst:`:php:` or :rst:`:php-short:`.

    code
        In a PHP example, which imports the class with a :samp:`use`
        statement. What the example then does with it is not counted again.

    definition
        The manual documents the class itself, with
        :rst:`.. php:class::` or one of its siblings.

members
    The members the text names, as they are written after the class:
    :samp:`::makeInstance()`, :samp:`->getAttribute()`, :samp:`::CONSTANT`.
    Left out where the text names the class alone, and only ever present on
    :samp:`inline`.

A class named twice in the same section is one place: an author repeating a
name does not make it a second mention. Naming several of its members there
is one place too, which lists them.

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
:ref:`Changelog <ChangelogIndex>` speaks of many classes that no longer
exist. There each place names the release of its entry, and the
:samp:`anchor` of a place in an entry is the entry's own, the key of the entry
in :file:`Changelog-<major>.json`.

Classes below :samp:`Vendor`, :samp:`MyVendor`, :samp:`Foo` or
:samp:`OriginalVendor` are left out: an example invents them for the reader to
replace with their own, so where they are spoken of says nothing. A manual
that invents other names says so on the extension, and names all of them --
the setting replaces the list above rather than adding to it:

..  code-block:: xml

    <extension class="\T3Docs\Typo3DocsTheme\DependencyInjection\Typo3DocsThemeExtension"
               example-vendors="Vendor, MyVendor, Acme"
    />

For TYPO3 Explained the file is about 830 KB, with 969 classes in 3084
places.
