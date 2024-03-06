.. include:: /Includes.rst.txt
.. highlight:: rst
.. index:: Panels
.. index:: pair: Bootstrap; Panels
.. index:: pair: Sphinx; Panels
.. _Sphinx-Panels:

=============
Sphinx panels
=============

Sphinx extension 'sphinx-panels'
   :Repository:
      `sphinx-panels@GitHub
      <https://github.com/executablebooks/sphinx-panels>`__
   :Documentation:
      `sphinx-panels@ReadTheDocs
      <https://sphinx-panels.readthedocs.io/en/latest/>`__


.. admonition:: Credit

   At the moment the examples on this page are mostly a one to one copy of the
   `sphinx-panels documentation <https://sphinx-panels.readthedocs.io/en/latest/>`__.


.. attention::

   These examples show what the extension is offering out of the box.

   The TYPO3 community hasn't yet agreed on what possibilities we actually want
   to use. Note that we are using a different and dedicated extension for
   :ref:`Sphinx-Tabs <Sphinx-Tabs>` already.


.. contents:: This page
   :backlinks: top
   :class: compact-list
   :depth: 99
   :local:


.. _panels/usage:

Dummy target 'panels/usage'
===========================
...


Examples
========

Example: Extension manual search
--------------------------------

.. panels::
   :container: container
   :column: col-12 padding-0-important
   :card:
   :header: bold-important

   Extension manual search
   ^^^^^^^^^^^^^^^^^^^^^^^

   fill in here


Example
-------

.. panels::

    Content of the top-left panel

    ---

    Content of the top-right panel

    :badge:`example,badge-primary`

    ---

    .. dropdown:: :fa:`eye,mr-1` Bottom-left panel

        Hidden content

    ---

    .. link-button:: https://example.org
        :text: Clickable Panel
        :classes: stretched-link



Example
-------

.. panels::

    Content of the top-left panel

    ---

    Content of the top-right panel

    ---

    Content of the bottom-left panel

    ---

    Content of the bottom-right panel

Example
-------

.. panels::

   .. link-button:: https://example.org
      :type: url
      :tooltip: hallo
      :classes: btn-success

   ---

   This entire panel is clickable.

   +++

   .. link-button:: panels/usage
      :type: ref
      :text: Go To Reference
      :classes: btn-outline-primary btn-block stretched-link

Example
-------

.. dropdown:: Click on me to see my content!

   I'm the content which can be anything:

   .. link-button:: https://example.org
       :text: Like a Button
       :classes: btn-primary

Example
-------

.. panels::
   :container: container-lg pb-3
   :column: col-lg-4 col-md-4 col-sm-6 col-xs-12 p-2

   panel1
   ---
   panel2
   ---
   panel3
   ---
   :column: col-lg-12 p-2
   panel4

Example
-------

.. panels::

   panel 1 header
   ^^^^^^^^^^^^^^

   panel 1 content

   more content

   ++++++++++++++
   panel 1 footer

   ---

   panel 2 header
   ^^^^^^^^^^^^^^

   panel 2 content

   ++++++++++++++
   panel 2 footer

Example
-------

.. panels::
   :body: bg-primary text-justify
   :header: text-center
   :footer: text-right

   ---
   :column: + p-1

   panel 1 header
   ^^^^^^^^^^^^^^

   panel 1 content

   ++++++++++++++
   panel 1 footer

   ---
   :column: + p-1 text-center border-0
   :body: bg-info
   :header: bg-success
   :footer: bg-secondary

   panel 2 header
   ^^^^^^^^^^^^^^

   panel 2 content

   ++++++++++++++
   panel 2 footer


.. panels::
   :img-top-cls: pl-5 pr-5

   ---
   :img-top: https://sphinx-panels.readthedocs.io/en/latest/_images/ebp-logo.png
   :img-bottom: https://sphinx-panels.readthedocs.io/en/latest/_images/footer-banner.jpg

   header 1
   ^^^^^^^^

   Panel 1 content

   More **content**

   ++++++
   tail 1

   ---
   :img-top: https://sphinx-panels.readthedocs.io/en/latest/_images/sphinx-logo.png
   :img-top-cls: + bg-success
   :img-bottom: https://sphinx-panels.readthedocs.io/en/latest/_images/footer-banner.jpg

   header 2
   ^^^^^^^^

   Panel 2 content

   ++++++
   tail 1


Example
=======

.. link-button:: https://example.org
   :type: url
   :text: some text
   :tooltip: hallo

.. link-button:: panels/usage
   :type: ref
   :text: some other text
   :classes: btn-outline-primary btn-block

Example
=======

.. panels::

   .. link-button:: https://example.org
      :classes: btn-success

   ---

   This entire panel is clickable.

   +++

   .. link-button:: panels/usage
      :type: ref
      :text: Go To Reference
      :classes: btn-outline-primary btn-block stretched-link

Example
=======

:badge:`primary,badge-primary`

:badge:`primary,badge-primary badge-pill`


Example
=======

:link-badge:`https://example.org,cls=badge-primary text-white,tooltip=a tooltip`
:link-badge:`https://example.org,"my, text",cls=badge-dark text-white`
:link-badge:`panels/usage,my reference,ref,badge-success text-white,hallo`

Example
=======

.. dropdown:: Click on me to see my content!

   I'm the content which can be anything:

   .. link-button:: https://example.org
      :text: Like a Button
      :classes: btn-primary

Example
=======
.. dropdown:: My Content
   :open:

   Is already visible

Example
=======

.. dropdown::

   My Content

Example
=======

.. dropdown:: My Content
   :container: + shadow
   :title: bg-primary text-white text-center font-weight-bold
   :body: bg-light text-right font-italic

   Is formatted

Example
=======

.. dropdown:: My content will fade in
   :animate: fade-in

   Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua.
   Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat.
   Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur.
   Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum.

Example
=======

.. dropdown:: My content will fade in and slide down
   :animate: fade-in-slide-down

   Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua.
   Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat.
   Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur.
   Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum.

Example
=======

.. tabbed:: Tab 1

   Tab 1 content

.. tabbed:: Tab 2
   :class-content: pl-1 bg-primary

   Tab 2 content

.. tabbed:: Tab 3
   :new-group:

   .. code-block:: python

      import pip

.. tabbed:: Tab 4
   :selected:

   .. dropdown:: Nested Dropdown

      Some content



Tabbed Content
==============

The ``tabbed`` directive generates tabbed selection panels.

Sequential directives will be grouped together, unless the ``:new-group`` option is added.
You can set which tab will be shown by default, using the ``:selected:`` option.

Tab directives can contain any content, and you can also set CSS classes with ``:class-label:`` and ``:class-content:``:

.. code-block:: rst

    .. tabbed:: Tab 1

        Tab 1 content

    .. tabbed:: Tab 2
        :class-content: pl-1 bg-primary

        Tab 2 content

    .. tabbed:: Tab 3
        :new-group:

        .. code-block:: python

            import pip

    .. tabbed:: Tab 4
        :selected:

        .. dropdown:: Nested Dropdown

            Some content

.. tabbed:: Tab 1

    Tab 1 content

.. tabbed:: Tab 2
    :class-content: pl-1 bg-primary

    Tab 2 content

.. tabbed:: Tab 3
    :new-group:

    .. code-block:: python

        import pip

.. tabbed:: Tab 4
    :selected:

    .. dropdown:: Nested Dropdown

        Some content

Here's an example of showing an example in multiple programming languages:

.. tabbed:: c++

    .. code-block:: c++

        int main(const int argc, const char **argv) {
          return 0;
        }

.. tabbed:: python

    .. code-block:: python

        def main():
            return

.. tabbed:: java

    .. code-block:: java

        class Main {
            public static void main(String[] args) {
            }
        }

.. tabbed:: julia

    .. code-block:: julia

        function main()
        end

.. tabbed:: fortran

    .. code-block:: fortran

        PROGRAM main
        END PROGRAM main



Div Directive
=============

The ``div`` directive is the same as the `container directive <https://docutils.sourceforge.io/docs/ref/rst/directives.html#container>`_,
but does not add a ``container`` class in HTML outputs, which is incompatible with Bootstrap CSS:

.. code-block:: rst

    .. div:: text-primary

        hallo

.. div:: text-primary

    hallo


Combined Example
================

.. code-block:: rst

    .. dropdown:: Panels in a drop-down
        :title: bg-success text-warning
        :open:
        :animate: fade-in-slide-down

        .. panels::
            :container: container-fluid pb-1
            :column: col-lg-6 col-md-6 col-sm-12 col-xs-12 p-2
            :card: shadow
            :header: border-0
            :footer: border-0

            ---
            :card: + bg-warning

            header
            ^^^^^^

            Content of the top-left panel

            ++++++
            footer

            ---
            :card: + bg-info
            :footer: + bg-danger

            header
            ^^^^^^

            Content of the top-right panel

            ++++++
            footer

            ---
            :column: col-lg-12 p-3
            :card: + text-center

            .. link-button:: panels/usage
                :type: ref
                :text: Clickable Panel
                :classes: btn-link stretched-link font-weight-bold

Example
=======

.. dropdown:: Panels in a drop-down
   :title: bg-success text-warning
   :open:
   :animate: fade-in-slide-down

   .. panels::
      :container: container-fluid pb-1
      :column: col-lg-6 col-md-6 col-sm-12 col-xs-12 p-2
      :card: shadow
      :header: border-0
      :footer: border-0

      ---
      :card: + bg-warning

      header
      ^^^^^^

      Content of the top-left panel

      ++++++
      footer

      ---
      :card: + bg-info
      :footer: + bg-danger

      header
      ^^^^^^

      Content of the top-right panel

      ++++++
      footer

      ---
      :column: col-lg-12 p-3
      :card: + text-center

      .. link-button:: panels/usage
         :type: ref
         :text: Clickable Panel
         :classes: btn-link stretched-link font-weight-bold








