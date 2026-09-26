..  include:: /Includes.rst.txt

..  _ConfvalIndexJson:

======================
Option index as JSON
======================

A manual that documents options with :rst:`..  confval::` is rendered with a
:file:`confvals.json` beside its :ref:`toc.json <TableOfContentsJson>`: every
option, with what the directive says about it, where it stands, and the
first paragraph of its description.

It lets a reader find an option by its name without reading the pages that
document it -- ninety of them for the TCA reference -- and pick the right one
when several share a name. :file:`objects.inv.json` lists the same options,
but by name and address alone: the TCA reference documents
:samp:`itemsProcFunc` eight times, once for each field type that has it, and
there all eight are called :samp:`itemsProcFunc` and nothing more.

..  code-block:: json

    {
      "project": {
        "title": "TCA Reference",
        "version": "main",
        "permalink": "https://docs.typo3.org/permalink/t3tca:{anchor}@main"
      },
      "confvals": {
        "confval-select-checkbox-itemsprocfunc": {
          "name": "itemsProcFunc",
          "context": [
            "Field types (config > type)",
            "Select fields",
            "selectCheckBox",
            "Properties of the TCA column type select with renderType selectCheckBox"
          ],
          "type": "string (class->method reference)",
          "fields": {
            "Path": "$GLOBALS['TCA'][$table]['columns'][$field]['config']",
            "Scope": "Display / Proc."
          },
          "summary": "PHP method which is called to fill or manipulate the items array. See itemsProcFunc about details.",
          "path": "ColumnsConfig/Type/Select/CheckBox/Index"
        }
      }
    }

The project is described the way :file:`toc.json` describes it. Each option
is keyed by its anchor, which the :samp:`permalink` there resolves, and says:

..  rst-class:: dl-parameters

name
    The name of the option, as the directive writes it.

context
    The titles above the option: the pages above its page in the table of
    contents, the sections of its page, and the options it is nested in. The
    start page is left out, since it is the manual. This is what tells apart
    options of the same name.

parent
    The key of the option this one is nested in. Left out for an option that
    stands on its own.

type, default, required
    The directive's own fields, as plain text. Left out where the directive
    does not give them; :samp:`required` is only ever :samp:`true`.

fields
    Every other field of the directive, by the name the manual gives it and
    as plain text. The names are the manual's own and differ between
    manuals: the TCA reference writes :samp:`Path` and :samp:`Scope`.

summary
    The first paragraph of the option's own description, as plain text.
    Left out for an option that has none, such as one that only holds the
    options nested in it.

path
    The page that documents the option, relative to this file and without an
    extension, as in :file:`toc.json`.

An option with :samp:`:noindex:` is left out, as it is from
:file:`objects.inv.json`. A manual without options writes no
:file:`confvals.json`.

For the TCA reference the file is about 530 KB with 757 options, for the
TypoScript reference about 580 KB with 1107.
