---
title: "Markdown rendering"
permalink: "https://docs.typo3.org/permalink/mdtest:markdown-rendering"
source: "index.rst"
start: true
rendered: "2023-01-01T12:00:00+00:00"
---

# Markdown rendering {#markdown-rendering}

A paragraph with **strong**, *emphasis* and `literal` text, plus a
[link to the TYPO3 website](https://typo3.org).

## A section {#a-section}

-   first bullet
-   second bullet

```php
$greeting = 'hello';
```

> [!NOTE]
> An admonition renders as a GFM alert.

There are the following subpages:

-   [Visible child](https://docs.typo3.org/permalink/mdtest:visible-child)
-   [A nested page](https://docs.typo3.org/permalink/mdtest:a-nested-page)

The navigation below is built but not shown:

A directive without a Markdown template leaves a marker naming it, and its
content is still rendered, so a gap is visible rather than silent:

<!-- TODO: no Markdown rendering for "not-a-real-directive" -->

The content of an unhandled directive is kept.
