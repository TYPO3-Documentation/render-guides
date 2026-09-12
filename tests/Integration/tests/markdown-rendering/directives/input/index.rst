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
