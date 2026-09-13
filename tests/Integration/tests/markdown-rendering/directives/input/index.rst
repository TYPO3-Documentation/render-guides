==========
Directives
==========

A horizontal list keeps its items but loses the columns, which Markdown
cannot lay out sideways.

..  hlist::
    :columns: 3

    *   A list of
    *   short items
    *   that would be
    *   side by side

A video cannot be embedded, so it becomes a link to itself.

..  youtube:: UdIYDZgBrQU

A glossary is its entries; the A-Z navigation around them does not survive.

..  glossary::

    CMS
        Content management system

    magic number
        A magic number is a magic number.

A diagram and a formula are source text that HTML turns into a picture.
Markdown shows the source, tagged for the renderers that know it.

..  uml::

    Alice -> Bob: Hello
    Bob --> Alice: Hi

..  math::

    a^2 + b^2 = c^2

A code block keeps its caption, which says where the snippet belongs.

..  code-block:: php
    :caption: EXT:my_extension/ext_localconf.php

    $GLOBALS['TYPO3_CONF_VARS']['SYS']['caching'] = [];

The "option" directive is how the manuals write a configuration value; it is
shaped like a confval.

..  option:: errorFluidTemplate

    The path to the Fluid template file.

A TYPO3 file definition carries both of its paths.

..  typo3:file:: ext_localconf.php
    :scope: extension
    :composer-path: my_extension/
    :classic-path: typo3conf/ext/my_extension/

    Included on every request.

