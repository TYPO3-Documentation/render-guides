..  include:: /Includes.rst.txt
..  _viewhelpers:

===========
ViewHelpers
===========

See :typo3:viewhelper:`typo3fluid-fluid-viewhelpers-splitviewhelper` or
:typo3:viewhelper-argument:`typo3-cms-fluid-viewhelpers-link-externalviewhelper-uri`.

Options: `:source:` (required), `:sortBy:` (default `name`), `:display:`
(default `tags, documentation, gitHubLink, arguments`) and `:noindex:`.

f:split
=======

..  typo3:viewhelper:: split
    :source: resources/global_viewhelpers_demo.json

..  typo3:viewhelper:: split
    :source: resources/global_viewhelpers_demo.json
    :sortBy: json
    :noindex:

f:link.external
===============

..  typo3:viewhelper:: link.external
    :source: resources/global_viewhelpers_demo.json


else
====

..  typo3:viewhelper:: else
    :source: resources/global_viewhelpers_demo.json

f:deprecated
============

..  typo3:viewhelper:: deprecated
    :source: resources/global_viewhelpers_demo.json


formvh:be.maximumFileSize
=========================

..  typo3:viewhelper:: be.maximumFileSize
    :source: resources/Form.json

display
=======

`:display:` selects which blocks are rendered, as a comma separated list.
Here only the documentation is shown — no tags, no arguments, no GitHub link:

..  typo3:viewhelper:: else
    :source: resources/global_viewhelpers_demo.json
    :display: documentation
    :noindex:

Linking
=======

*   a ViewHelper — :typo3:viewhelper:`typo3-cms-fluid-viewhelpers-link-externalviewhelper`
*   an argument — :typo3:viewhelper-argument:`typo3fluid-fluid-viewhelpers-splitviewhelper-separator`
*   with custom link text — :typo3:viewhelper:`the split ViewHelper <typo3fluid-fluid-viewhelpers-splitviewhelper>`
