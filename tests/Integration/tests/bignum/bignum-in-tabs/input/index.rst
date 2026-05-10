=========================================================
Tabs containing rst-class sibling list (issue 1236)
=========================================================

The ``bignums-tip`` rst-class must apply to the ``<ol>`` and must NOT leak
its own class name as text into the rendered tab pane.

..  tabs::

    ..  group-tab:: Extension Manager

        ..  rst-class:: bignums-tip

        #.  Switch to the module :guilabel:`System > Extensions`.
        #.  Switch to :guilabel:`Get Extensions`.
        #.  Search for the extension key :guilabel:`example_extension`.
        #.  Import the extension from the repository.
