=========================
Markdown escaping of text
=========================

Source prose is not Markdown, so characters that would start a Markdown
construct have to be escaped. Characters that cannot start one are left
alone, otherwise the output fills up with backslashes.

Emphasis characters
===================

An underscore inside a word is not emphasis in CommonMark and stays as it
is: snake_case_name. One at a word boundary can open emphasis and is
escaped: __dunder__ names.

A matched pair of asterisks is emphasis already in the source and stays
emphasis: 2*3*4 renders as a product with an emphasised 3. An unmatched one
is text and is escaped so it cannot open emphasis here: 5 * 3 equals 15.

Links and code
==============

Brackets would open a link: see [1] and array[0](x).

A backtick would open a code span: use ` to quote.

An angle bracket only matters before a letter: 3 < 4 stays, but <b>bold</b>
is escaped.

Block markers at the start of a line
====================================

| # not a heading
| - not a list item
| 1. not an ordered item
| > not a quote
| -1 is not a list either

Backslashes
===========

A doubled backslash in the source is one literal backslash, and Markdown
needs it doubled again: C:\\temp\\file.

Inside code spans
=================

Nothing is escaped inside a code span, because backticks already take the
content literally: :php:`$a[0] . "_" . $b`.
