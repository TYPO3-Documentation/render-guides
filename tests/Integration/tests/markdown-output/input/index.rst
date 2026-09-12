==================
Markdown rendering
==================

A paragraph with **strong**, *emphasis* and ``literal`` text, plus a
`link to the TYPO3 website <https://typo3.org>`__.

A section
=========

*   first bullet
*   second bullet

..  code-block:: php

    $greeting = 'hello';

..  note::

    An admonition renders as a GFM alert.

There are the following subpages:

..  toctree::

    visible-child

The navigation below is built but not shown:

..  toctree::
    :hidden:

    hidden-child

A directive without a Markdown template yet leaves a marker:

..  tabs::

    ..  group-tab:: One

        First tab.

    ..  group-tab:: Two

        Second tab.
