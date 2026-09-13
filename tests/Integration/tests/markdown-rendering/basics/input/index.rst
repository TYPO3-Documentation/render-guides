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
    sub/nested

The navigation below is built but not shown:

..  toctree::
    :hidden:

    hidden-child

A directive without a Markdown template leaves a marker naming it, and its
content is still rendered, so a gap is visible rather than silent:

..  not-a-real-directive::

    The content of an unhandled directive is kept.
