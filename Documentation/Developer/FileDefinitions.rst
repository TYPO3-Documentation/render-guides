..  include:: /Includes.rst.txt

..  _FileDefinitionsJson:

=================================
File definitions across manuals
=================================

A :rst:`:file:` text role links to the :rst:`..  typo3:file::` definition it
names, with a popup that says where the file lives in Composer and Classic mode
installations. The official manuals define their files in one place, TYPO3
Explained, so every other manual links them to TYPO3 Explained:

#.  A :rst:`:file:` is matched against the definitions of the manual it is in
    first. A file a manual defines itself always wins.
#.  If none matches, it is matched against the definitions of TYPO3 Explained,
    in the version the manual's interlinks to :samp:`t3coreapi` go to.
#.  If none matches there either, the file is rendered as plain code, as
    before.

Within each of these, a file named by its id wins over one whose
:samp:`:regex:` matches, and otherwise the first matching regex does.

Nothing has to change in a manual for this. TYPO3 Explained itself never looks
itself up. A manual that cannot fetch the definitions -- rendered offline, or
while TYPO3 Explained is not yet rendered with a theme that writes them --
renders its files as plain code and warns about nothing.

The file TYPO3 Explained publishes
==================================

A manual that defines files is rendered with a :file:`files.json` at its root:

..  code-block:: json

    {
      "project": {
        "title": "TYPO3 Explained",
        "version": "main"
      },
      "files": [
        {
          "id": "extension-ext-tables-sql",
          "fileName": "ext_tables.sql",
          "language": "",
          "scope": "extension",
          "composerPath": "",
          "composerPathPrefix": "packages/my_extension/",
          "classicPath": "",
          "classicPathPrefix": "typo3conf/ext/my_extension/",
          "regex": "/^.*ext\\_tables\\.sql$/",
          "shortDescription": "Holds additional SQL definition of database tables.",
          "path": "ExtensionArchitecture/FileStructure/ExtTablesSql.html#file-extension-ext-tables-sql"
        }
      ]
    }

Each entry carries the options of its :rst:`..  typo3:file::` directive, as the
popup shows them, and:

..  rst-class:: dl-parameters

id
    The id the definition is known by, and that a :rst:`:file:` may name
    instead of a path.

regex
    What a :rst:`:file:` is matched with. :samp:`""` for a file that can only
    be named by its id.

path
    The page and anchor that define the file, relative to this file.

A manual without definitions writes no :file:`files.json`. A definition with
:samp:`:noindex:` is left out, as it is from the manual's own lookup.

Why it exists
=============

:file:`objects.inv.json` has the address of every definition, but not its
regex, and a :rst:`:file:` rarely names a file by its id: it names a path such
as :file:`EXT:my_extension/ext_localconf.php`, and the regex decides which file
that is.

Measured on 2026-09-24 against a render of TYPO3 Explained, which defines 59
files: 391 of the Core Changelog's :rst:`:file:` roles and 58 of the TCA
Reference's link to it now.
