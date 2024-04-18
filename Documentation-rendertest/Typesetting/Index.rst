.. include:: /Includes.rst.txt

..  _typesetting:

=================
Typesetting `code`
==================

..  contents:: Table of contents

Introduction
============

This is a quick demonstration of some common constructs. This page can be used
to improve styles. It is not a complete list of features of restructured text.

Refer to the specific pages for more examples.

- **Headers**
- *Emphasis*
- `Inline code`
- Lists
- Blockquotes
- Tables

..  _headers:

Headers
=======

There are multiple levels of headers available:

Level 2 Header `code`
=====================

Lorem ipsum dolor sit amet, consetetur sadipscing elitr, sed diam nonumy eirmod
tempor invidunt ut labore et dolore magna aliquyam erat, sed diam voluptua.

Level 3 Header `code`
---------------------

At vero eos et accusam et justo duo dolores et ea rebum. Stet clita kasd
gubergren, no sea takimata sanctus est Lorem ipsum dolor sit amet.
Lorem ipsum dolor sit amet, consetetur sadipscing elitr, sed diam nonumy eirmod
tempor invidunt ut labore et dolore magna aliquyam erat, sed diam voluptua.

Level 4 Header `code`
~~~~~~~~~~~~~~~~~~~~~

At vero eos et `accusam` et justo duo dolores et ea rebum. Stet clita kasd
gubergren, no sea takimata sanctus est Lorem ipsum dolor sit amet.

Level 5 Header
++++++++++++++

At vero eos et accusam et justo duo dolores et ea rebum. Stet clita kasd
gubergren, no sea takimata sanctus est Lorem ipsum dolor sit amet.

Level 6 Header
##############

At vero eos et accusam et justo duo dolores et ea rebum. Stet clita kasd
gubergren, no sea takimata sanctus est Lorem ipsum dolor sit amet.

..  _emphasis:

Emphasis
========

Emphasizing text can be done using asterisks or underscores:

*This text is emphasized.* _This text is also emphasized._

Code
====

Inline code
-----------

You can highlight inline code using backticks: `some code`, :php:`$somePHP`.
See also :ref:`Inline-code-and-text-roles`.

Code blocks
-----------

For displaying larger code snippets, use code blocks:

..  code-block:: php

    <?php
    function greet($name) {
        echo "Hello, $name!";
    }

    greet("world");

See also :ref:`Codeblocks`.

Lists
=====

Lists can be unordered or ordered:

Unordered List:

*   Item 1
*   Item 2

    *   Subitem 1
    *   Subitem 2

*   Item 3

Ordered List:

1.  First item
2.  Second item
3.  Third item

See also :ref:`lists`.

Blockquotes
===========

You can include blockquotes by indenting them:

    This is a blockquote.
    It can span multiple lines.

See also :ref:`Block-Quotes`.

Tables
======

Tables are represented using pipes and dashes:

+--------------+---------------+
| Name         | Occupation    |
+==============+===============+
| John Doe     | Programmer    |
+--------------+---------------+
| Jane Smith   | Designer      |
+--------------+---------------+

See also :ref:`tables`.

References
----------

Here's a reference to a section:

*   :ref:`Headers section <headers>`
*   :ref:`Emphasis section <emphasis>`
*   https://typo3.org/

See also :ref:`references-and-links`.
