---
title: "Single file rendering"
version: "13.4"
release: "13.4.2"
modified: "2023-01-01T12:00:00+00:00"
single-file: true
---

<a id="single-file-start"></a>

# Single file rendering

The whole project ends up in one Markdown file, with one front matter block
for the project instead of one per page.

-   [First page](https://docs.typo3.org/permalink/mdtest:first-page@13.4)
-   [Second page](https://docs.typo3.org/permalink/mdtest:second-page@13.4)
-   [Sitemap](https://docs.typo3.org/permalink/mdtest:sitemap@13.4)

---

<a id="single-file-first"></a>

# First page

A paragraph with **strong** text and a link to
[the second page](#single-file-second).

> [!NOTE]
> An admonition still renders as a GFM alert inside the single file.

---

<a id="single-file-second"></a>

# Second page

A page in a subdirectory, so its position in the single file does not depend
on where it lives on disk.

-   first bullet
-   second bullet
