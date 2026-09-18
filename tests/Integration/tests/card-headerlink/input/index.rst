..  _card-headerlink:

=========================
Cards carry no permalink
=========================

A card title is not a heading anybody can link to, so it gets no permalink
button. The section heading above it does.

..  card-grid::
    :columns: 1

    ..  card:: A card with a title

        Its title is rendered as a heading, but the card has no anchor.

    ..  card:: Another card

        The id the library gives both titles is the name of the directive.
