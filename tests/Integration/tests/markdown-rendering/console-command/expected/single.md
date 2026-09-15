---
title: "A single command"
permalink: "https://docs.typo3.org/permalink/mdconsole:a-single-command"
source: "single.rst"
modified: "2023-01-01T12:00:00+00:00"
---

# A single command {#a-single-command}

A command declared on its own, with the script that invokes it in front of its
name. It repeats a command from the overview, so it is not indexed again.

-   **vendor/bin/typo3 cache:flush**

    Flush TYPO3 caches.

    *Usage*

    ```bash
    vendor/bin/typo3 cache:flush [-g|--group [GROUP]]
    ```

    *Options*

    -   **--group / -g**

        -   *Value:* Optional
        -   *Default value:* "all"

        The cache group to flush (system, pages, di or all)

    *Help*

    Flushes the caches of the given group.
