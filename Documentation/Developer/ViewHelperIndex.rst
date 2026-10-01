..  include:: /Includes.rst.txt

..  _ViewHelperIndexJson:

========================
ViewHelper index as JSON
========================

A manual that documents ViewHelpers with :rst:`..  typo3:viewhelper::` is
rendered with a :file:`viewhelpers.json` beside its
:ref:`toc.json <TableOfContentsJson>`. It lists every ViewHelper by the name a
template writes, with what the file describing it says and where the manual
shows it:

..  code-block:: json

    {
      "project": {
        "title": "Fluid ViewHelper Reference",
        "version": "main",
        "permalink": "https://docs.typo3.org/permalink/t3viewhelper:{anchor}@main"
      },
      "viewhelpers": {
        "f:format.html": {
          "class": "TYPO3\\CMS\\Fluid\\ViewHelpers\\Format\\HtmlViewHelper",
          "namespace": "TYPO3\\CMS\\Fluid\\ViewHelpers",
          "xmlNamespace": "http://typo3.org/ns/TYPO3/CMS/Fluid/ViewHelpers",
          "summary": "ViewHelper to render a string which can contain HTML markup …",
          "documentation": "ViewHelper to render a string …",
          "docTags": {
            "@see": "https://docs.typo3.org/permalink/t3tsref:parsefunc"
          },
          "allowsArbitraryArguments": false,
          "source": "https://github.com/TYPO3/typo3/blob/main/…/Format/HtmlViewHelper.php",
          "arguments": {
            "current": {
              "type": "string",
              "required": false,
              "description": "Initialize the content object with this value …",
              "anchor": "viewhelper-argument-typo3-cms-fluid-viewhelpers-format-htmlviewhelper-current",
              "permalink": "https://docs.typo3.org/permalink/t3viewhelper:viewhelper-argument-…-current@main"
            }
          },
          "path": "Global/Format/Html",
          "anchor": "viewhelper-typo3-cms-fluid-viewhelpers-format-htmlviewhelper",
          "permalink": "https://docs.typo3.org/permalink/t3viewhelper:viewhelper-typo3-cms-fluid-viewhelpers-format-htmlviewhelper@main"
        }
      }
    }

A ViewHelper is keyed by the prefix of its namespace, the
:samp:`namespaceAlias` of the describing file, and its tag name:
:samp:`f:format.html`, :samp:`be:moduleLink`, :samp:`core:icon`. That is the
name a template uses and a reader looks for. :file:`objects.inv.json` lists the
same ViewHelpers by a key made from the class and a title without the prefix,
and says nothing about them.

Each ViewHelper carries:

..  rst-class:: dl-parameters

class, namespace, xmlNamespace
    As the describing file states them.

summary
    The first paragraph of the documentation, as plain text.

documentation
    The documentation as the describing file writes it.

docTags
    The doc tags of the class, such as :samp:`@see` or :samp:`@deprecated`.
    Left out where there are none.

allowsArbitraryArguments
    Whether the ViewHelper takes arguments it does not declare.

source
    The class on GitHub, where the describing file says where its sources
    are.

arguments
    Each argument by its name, with its :samp:`type`, whether it is
    :samp:`required`, its :samp:`default` as a PHP literal where it has one,
    its :samp:`description`, and its own :samp:`anchor` and
    :samp:`permalink`.

path, anchor, permalink
    The page that shows the ViewHelper, relative to this file and without an
    extension as in :file:`toc.json`, its anchor, and the permalink built
    from it. A manual without an interlink shortcode has no permalinks.

A ViewHelper shown with :samp:`:noindex:` is listed where it is documented,
not where it is shown again. A manual without ViewHelpers writes no
:file:`viewhelpers.json`.

For the Fluid ViewHelper Reference the file lists 159 ViewHelpers with 712
arguments in about 630 KB.
