..  _start:

==========================
Links to the manual itself
==========================

A link of the manual to itself resolves within the render, also where it
names the version being rendered. A link to another version goes to that
version's inventory.

*   :ref:`By the shortcode <t3coreapi:self-target>`
*   :ref:`By the version being rendered <t3coreapi/main:self-target>`
*   :ref:`t3coreapi/main:self-target`
*   :doc:`A document by the shortcode <t3coreapi:Other>`
*   :doc:`A document by the version being rendered <t3coreapi/main:Other>`
*   `A permalink <https://docs.typo3.org/permalink/t3coreapi:self-target>`__
*   :ref:`Another version <t3coreapi/12:about>`

..  typo3:file:: ext_self_link.typoscript
    :scope: extension
    :regex: /^.*ext\_self\_link\.typoscript$/
    :shortDescription: A file whose description links to the manual

    The description of a file, which the compiler does not walk:
    :ref:`a link to the manual itself <t3coreapi:self-target>`.

..  toctree::

    Other
