..  include:: /Includes.rst.txt
..  _menu-directive:

====
menu
====

`..  menu::` renders a navigation menu of other pages in the body of the page.

It behaves like `..  toctree::` except that `:glob:` is always switched on, so
the entries are always treated as patterns. Use `toctree` when the pages should
also be added to the sidebar navigation, and `menu` when only an in-page list is
wanted.

A page is never listed in its own menu, so there is no need to exclude it.
`:globExclude:` is accepted but currently has no effect on `menu`.


Example - all sibling pages
===========================

Source:

..  code-block:: rst

    ..  menu::

        *

Result:

..  menu::

    *
