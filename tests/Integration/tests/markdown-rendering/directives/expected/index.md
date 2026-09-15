---
title: "Directives"
permalink: "https://docs.typo3.org/permalink/mddirectives:directives"
source: "index.rst"
start: true
rendered: "2023-01-01T12:00:00+00:00"
---

# Directives {#directives}

A horizontal list keeps its items but loses the columns, which Markdown
cannot lay out sideways.

-   A list of
-   short items
-   that would be
-   side by side

A video cannot be embedded, so it becomes a link to itself.

[Watch this video on YouTube](https://www.youtube.com/watch?v=UdIYDZgBrQU)

A glossary is its entries; the A-Z navigation around them does not survive.

-   **CMS**

    Content management system

-   **magic number**

    A magic number is a magic number.

A diagram and a formula are source text that HTML turns into a picture.
Markdown shows the source, tagged for the renderers that know it.

```plantuml
Alice -> Bob: Hello
Bob --> Alice: Hi
```

```math
a^2 + b^2 = c^2
```

A code block keeps its caption, which says where the snippet belongs.

**EXT:my_extension/ext_localconf.php**

```php
$GLOBALS['TYPO3_CONF_VARS']['SYS']['caching'] = [];
```

The "option" directive is how the manuals write a configuration value; it is
shaped like a confval.

-   **errorFluidTemplate**

    The path to the Fluid template file.

A TYPO3 file definition carries both of its paths.

-   **ext_localconf.php**

    -   *Scope:* extension
    -   *Path (Composer):* packages/my_extension/ext_localconf.php
    -   *Path (Classic):* typo3conf/ext/my_extension/ext_localconf.php

    Included on every request.
