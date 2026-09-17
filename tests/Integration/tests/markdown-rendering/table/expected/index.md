---
title: "Tables"
permalink: "https://docs.typo3.org/permalink/mdtable:tables"
source: "index.rst"
start: true
rendered: "2023-01-01T12:00:00+00:00"
---

# Tables {#tables}

A table with cells becomes a GFM pipe table.

| Header1 | Header2 |
| --- | --- |
| 1 | one |

A grid table keeps its header row too.

| Header1 | Header2 |
| --- | --- |
| 1 | one |

A header row narrower than the body is widened: GFM ignores a body cell the
header has no column for, so the table would lose "one" without it.

| Only one header |  |
| --- | --- |
| 1 | one |

GFM knows one header row, so a table with two of them writes the second as a
body row rather than dropping it.

| Head A1 | Head B1 |
| --- | --- |
| Head A2 | Head B2 |
| 1 | one |

A list item that is not a field list is reported by name.

|  |
| --- |
| 1 |

A directive whose content is missing -- the sections below it were never
indented into it -- leaves a table with no cell at all. GFM cannot spell one,
so nothing is written.

The text after it is still part of the document.
