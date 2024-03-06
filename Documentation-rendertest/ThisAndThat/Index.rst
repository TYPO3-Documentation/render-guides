.. include:: /Includes.rst.txt


=============
This and that
=============

.. contents:: This page
   :backlinks: top
   :class: compact-list
   :depth: 99
   :local:


Maaaaath!
=========

This is a test. Here is an equation:
:math:`X_{0:5} = (X_0, X_1, X_2, X_3, X_4)`.
Here is another:

.. math::

    \nabla^2 f =
    \frac{1}{r^2} \frac{\partial}{\partial r}
    \left( r^2 \frac{\partial f}{\partial r} \right) +
    \frac{1}{r^2 \sin \theta} \frac{\partial f}{\partial \theta}
    \left( \sin \theta \, \frac{\partial f}{\partial \theta} \right) +
    \frac{1}{r^2 \sin^2\theta} \frac{\partial^2 f}{\partial \phi^2}


Rubric
======

   This directive creates a paragraph heading that is not used to create a
   table of contents node.

   -- `sphinx-doc.org
      <https://www.sphinx-doc.org/en/master/usage/restructuredtext/directives.html?highlight=rubric#directive-rubric>`__

.. rubric:: Rubric 001

On we go.

.. rubric:: Rubric 002
.. nothing in between
.. rubric:: Rubric 003





Subsection 1
------------

.. rubric:: Rubric sub 001

On we go.

.. rubric:: Rubric sub 002
.. nothing in between
.. rubric:: Rubric sub 003


Centered
========

   This directive creates a centered boldfaced line of text. Use it as follows:

   Deprecated since version 1.1: This presentation-only directive is a legacy
   from older versions. Use a rst-class directive instead and add an
   appropriate style.


   -- `sphinx-doc.org
      <https://www.sphinx-doc.org/en/master/usage/restructuredtext/directives.html?highlight=rubric#directive-centered>`__


.. centered:: SOMETHING THAT IS - MAYBE - CENTERED

`.. rst-class:: centered` should be used instead of `.. centered::`.



Hlist
=====

   This directive must contain a bullet list. It will transform it into a more
   compact list by either distributing more than one item horizontally, or
   reducing spacing between items, depending on the builder.

   For builders that support the horizontal distribution, there is a columns
   option that specifies the number of columns; it defaults to 2. Example:

   -- `sphinx-doc.org
      <https://www.sphinx-doc.org/en/master/usage/restructuredtext/directives.html?highlight=rubric#directive-hlist>`__

.. hlist::
   :columns: 3

   * A list of
   * short items
   * that should be
   * displayed
   * horizontally



Optional parameter args
=======================

At this point optional parameters `cannot be generated from code`_.
However, some projects will manually do it, like so:

This example comes from `django-payments module docs`_.

.. class:: payments.dotpay.DotpayProvider(seller_id, pin[, channel=0[, lock=False], lang='pl'])

   This backend implements payments using a popular Polish gateway, `Dotpay.pl <http://www.dotpay.pl>`_.

   Due to API limitations there is no support for transferring purchased items.


   :param seller_id: Seller ID assigned by Dotpay
   :param pin: PIN assigned by Dotpay
   :param channel: Default payment channel (consult reference guide)
   :param lang: UI language
   :param lock: Whether to disable channels other than the default selected above

.. _cannot be generated from code: https://groups.google.com/forum/#!topic/sphinx-users/_qfsVT5Vxpw
.. _django-payments module docs: http://django-payments.readthedocs.org/en/latest/modules.html#payments.authorizenet.AuthorizeNetProvider

Code test
=========

parsed-literal
--------------

.. parsed-literal::

    # parsed-literal test
    curl -O http://someurl/release-|version|.tar-gz

code-block
----------

.. code-block:: json

    {
    "windows": [
        {
        "panes": [
            {
            "shell_command": [
                "echo 'did you know'",
                "echo 'you can inline'"
            ]
            },
            {
            "shell_command": "echo 'single commands'"
            },
            "echo 'for panes'"
        ],
        "window_name": "long form"
        }
    ],
    "session_name": "shorthands"
    }

Sidebar
=======

.. sidebar:: Ch'ien / The Creative

    *Above* CH'IEN THE CREATIVE, HEAVEN

    .. image:: ../static/yi_jing_01_chien.jpg

    *Below* CH'IEN THE CREATIVE, HEAVEN

The first hexagram is made up of six unbroken lines. These unbroken lines stand for
the primal power, which is light-giving, active, strong, and of the spirit. The hexagram
is consistently strong in character, and since it is without weakness, its essence is
power or energy. Its image is heaven. Its energy is represented as unrestricted by any
fixed conditions in space and is therefore conceived of as motion. Time is regarded as
the basis of this motion. Thus the hexagram includes also the power of time and the
power of persisting in time, that is, duration.

The power represented by the hexagram is to be interpreted in a dual sense in terms
of its action on the universe and of its action on the world of men. In relation to
the universe, the hexagram expresses the strong, creative action of the Deity. In
relation to the human world, it denotes the creative action of the holy man or sage,
of the ruler or leader of men, who through his power awakens and develops their
higher nature.


Code with Sidebar
=================

.. sidebar:: A code example

    With a sidebar on the right.



Inline code and references
==========================

`reStructuredText`_ is a markup language. It can use roles and
declarations to turn reST into HTML.

In reST, ``*hello world*`` becomes ``<em>hello world</em>``. This is
because a library called `Docutils`_ was able to parse the reST and use a
``Writer`` to output it that way.

If I type ````an inline literal```` it will wrap it in ``<tt>``. You can
see more details on the `Inline Markup`_ on the Docutils homepage.

Also with ``sphinx.ext.autodoc``, which I use in the demo, I can link to
``:class:`test_py_module.test.Foo```. It will link you right my code
documentation for it.

.. _reStructuredText: http://docutils.sourceforge.net/rst.html
.. _Docutils: http://docutils.sourceforge.net/
.. _Inline Markup: http://docutils.sourceforge.net/docs/ref/rst/restructuredtext.html#inline-markup

.. note:: Every other line in this table will have white text on a white background.
            This is bad.

    +---------+
    | Example |
    +=========+
    | Thing1  |
    +---------+
    | Thing2  |
    +---------+
    | Thing3  |
    +---------+

Emphasized lines with line numbers
==================================

.. code-block:: python
   :linenos:
   :emphasize-lines: 3,5

   def some_function():
       interesting = False
       print 'This line is highlighted.'
       print 'This one is not...'
       print '...but this one is.'


Citation
========

Here I am making a citation [1]_

.. [1] This is the citation I made, let's make this extremely long so that we can tell that it doesn't follow the normal responsive table stuff.

Download links
==============

:download:`This long long long long long long long long long long long long long long long download link should be blue with icon, and should wrap white-spaces <../static/yi_jing_01_chien.jpg>`



typolink
========

Wraps the incoming value with a link.

If this is used from parseFunc the $cObj->parameters-array is loaded
with the link-parameters (lowercased)!

extTarget
---------

:aspect:`Property`
      extTarget

:aspect:`Data type`
      target /:ref:`stdWrap <t3tsref:stdwrap>`

:aspect:`Description`
      Target used for external links

:aspect:`Default`
      \_top


fileTarget
----------

:aspect:`Property`
      fileTarget

:aspect:`Data type`
      target /:ref:`stdWrap <t3tsref:stdwrap>`

:aspect:`Description`
      Target used for file links


target
------

:aspect:`Property`
      target

:aspect:`Data type`
      target /:ref:`stdWrap <t3tsref:stdwrap>`

:aspect:`Description`
      Target used for internal links




typolink
========

Wraps the incoming value with a link.

If this is used from parseFunc the $cObj->parameters-array is loaded
with the link-parameters (lowercased)!


.. container:: table-row

   Property
         extTarget

   Data type
         target /:ref:`stdWrap <stdwrap>`

   Description
         Target used for external links

   Default
         \_top


.. container:: table-row

   Property
         fileTarget

   Data type
         target /:ref:`stdWrap <t3tsref:stdwrap>`

   Description
         Target used for file links


.. container:: table-row

   Property
         target

   Data type
         target /:ref:`stdWrap <t3tsref:stdwrap>`

   Description
         Target used for internal links


.. ###### END~OF~TABLE ######
