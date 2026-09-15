---
title: "Console commands"
permalink: "https://docs.typo3.org/permalink/mdconsole:console-commands"
source: "index.rst"
start: true
rendered: "2023-01-01T12:00:00+00:00"
---

# Console commands {#console-commands}

The overview lists every command and renders each one below the table. A
hidden command is shown because "show-hidden" is set.

| Command | Description | Hidden |
| --- | --- | --- |
| **global** |  |  |
| [`_complete`](https://docs.typo3.org/permalink/mdconsole:console-command-complete) | Internal command to provide shell completion suggestions | True |
| **cache** |  |  |
| [`cache:flush`](https://docs.typo3.org/permalink/mdconsole:console-command-cache-flush) | Flush TYPO3 caches. |  |
| **language** |  |  |
| [`language:update`](https://docs.typo3.org/permalink/mdconsole:console-command-language-update) | Update the language files of all activated extensions |  |

-   **cache:flush**

    Flush TYPO3 caches.

    *Usage*

    ```bash
    cache:flush [-g|--group [GROUP]]
    ```

    *Options*

    -   **--group / -g**

        -   *Value:* Optional
        -   *Default value:* "all"

        The cache group to flush (system, pages, di or all)

    *Help*

    Flushes the caches of the given group.

-   **language:update**

    Update the language files of all activated extensions

    *Usage*

    ```bash
    language:update [--skip-extension SKIP-EXTENSION] [--] [<locales>...]
    ```

    *Arguments*

    -   **locales**

        Provide iso codes separated by space to update only selected language packs.

    *Options*

    -   **--skip-extension**

        -   *Value:* Required (multiple)
        -   *Default value:* \[\]

        Skip extension, for example one that has no language packs.

-   **\_complete**

    Internal command to provide shell completion suggestions

    *Usage*

    ```bash
    _complete
    ```
