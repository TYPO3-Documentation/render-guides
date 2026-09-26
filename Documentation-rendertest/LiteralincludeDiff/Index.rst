..  include:: /Includes.rst.txt

..  _LiteralincludeDiff:

==========================
Literal include with diffs
==========================

With :rst:`:diff:`, a :rst:`literalinclude` shows the changes from the file the
option names to the included file, as a unified diff. The author keeps both
files, the way they were and the way they are, instead of writing the diff by
hand.

Only the changed lines are shown, each with one unchanged line above and below
it. The file and hunk headers and the other unchanged lines are folded: click
a placeholder to open it, or the button beside the copy button to open them
all. :rst:`:visible-lines:` names other lines to show, and
:rst:`:visible-lines: all` shows the whole diff.

..  contents:: This page
    :local:

A configuration that gains a language
=====================================

..  code-block:: rst

    ..  literalinclude:: _codesnippets/_config-after.yaml
        :diff: _codesnippets/_config-before.yaml
        :caption: config/sites/my-site/config.yaml

..  literalinclude:: _codesnippets/_config-after.yaml
    :diff: _codesnippets/_config-before.yaml
    :caption: config/sites/my-site/config.yaml

A class that changes in several places
======================================

Changes that lie close together share one hunk, with three lines of context
around them. The other options still apply: :rst:`:emphasize-lines:` counts
the lines of the diff, here the new constructor.

..  code-block:: rst

    ..  literalinclude:: _codesnippets/_GreetingService-after.php
        :diff: _codesnippets/_GreetingService-before.php
        :caption: EXT:my_extension/Classes/Service/GreetingService.php
        :emphasize-lines: 15-17

..  literalinclude:: _codesnippets/_GreetingService-after.php
    :diff: _codesnippets/_GreetingService-before.php
    :caption: EXT:my_extension/Classes/Service/GreetingService.php
    :emphasize-lines: 15-17

The whole diff
==============

..  code-block:: rst

    ..  literalinclude:: _codesnippets/_config-after.yaml
        :diff: _codesnippets/_config-before.yaml
        :visible-lines: all

..  literalinclude:: _codesnippets/_config-after.yaml
    :diff: _codesnippets/_config-before.yaml
    :visible-lines: all
