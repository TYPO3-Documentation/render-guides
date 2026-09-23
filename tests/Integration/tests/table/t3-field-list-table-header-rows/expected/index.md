---
title: "Header rows"
source: "index.rst"
start: true
rendered: "2023-01-01T12:00:00+00:00"
---

# Header rows {#header-rows}

No header row:

|  |  |
| --- | --- |
| first | second |
| 1 | two |

Two header rows:

| first | second |
| --- | --- |
| third | fourth |
| 1 | two |

Without the option, the first row is the header:

| first | second |
| --- | --- |
| 1 | two |

An invalid value warns and keeps the first row as header:

| first | second |
| --- | --- |
| 1 | two |
