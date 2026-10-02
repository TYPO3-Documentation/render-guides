..  include:: /Includes.rst.txt

..  _ManualsIndexJson:

=====================
Manuals index as JSON
=====================

Every manual publishes machine-readable indexes of its own beside its pages:
:ref:`toc.json <TableOfContentsJson>`, :file:`objects.inv.json`,
:ref:`classes.json <ClassIndexJson>` and more. What was missing is a list of
the manuals themselves. The docs homepage renders one, :file:`manuals.json`,
beside the :file:`mainmenu.json` of the "All documentation" menu and from the
same menu:

..  code-block:: json

    {
      "manuals": {
        "t3tca": {
          "name": "TCA Reference",
          "group": "References",
          "versions": [
            {
              "version": "14.3",
              "base": "https://docs.typo3.org/m/typo3/reference-tca/14.3/en-us/"
            },
            {
              "version": "main",
              "base": "https://docs.typo3.org/m/typo3/reference-tca/main/en-us/"
            }
          ]
        }
      },
      "files": {
        "llms.txt": "every manual",
        "toc.json": "every manual",
        "objects.inv.json": "every manual",
        "classes.json": "every manual",
        "confvals.json": "a manual that documents options",
        "files.json": "a manual that defines files",
        "viewhelpers.json": "a manual that documents ViewHelpers"
      }
    }

Each manual is keyed by its interlink shortcode, the name that
:samp:`:ref:` and permalinks use for it. The menu writes its links as
interlink references, so the shortcode comes from the menu itself and
cannot be missing. A manual carries:

..  rst-class:: dl-parameters

name
    The name the menu gives the manual, from the entry that links its start
    page. An entry that points into a manual, such as "Administration" into
    TYPO3 Explained, adds no manual of its own.

group
    The heading of the menu the manual is listed under.

versions
    Each version the menu offers: :samp:`version` as it appears in the
    address, and :samp:`base`, the address the manual's files are below.

:samp:`files` names the indexes a manual can have. A consumer appends one to a
:samp:`base`; :file:`llms.txt` is where to start reading. The file is not checked for: a version last rendered before a
file was introduced answers 404 for it until it is rendered again.

The menu lists the official manuals and the system extensions. Extensions of
other vendors are not in it, and so not in :file:`manuals.json`.
