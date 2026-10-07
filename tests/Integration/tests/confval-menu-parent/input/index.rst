==============
Content object
==============

..  confval:: TEXT
    :name: cobj-text
    :type: cObject

    Outputs static text or HTML.

The menu lists the properties of TEXT, without TEXT itself. Another object
on the same page is left out by hand:

..  confval-menu::
    :name: text-properties
    :display: table
    :parent: cobj-text
    :exclude: cobj-other
    :type:

value
=====

..  confval:: value
    :name: text-value
    :type: string

    The text to output.

stdWrap
=======

..  confval:: stdWrap
    :name: text-stdwrap
    :type: stdWrap

    The stdWrap properties of the text.

    ..  confval:: wrap
        :name: text-stdwrap-wrap
        :type: wrap

        A property nested in stdWrap stays its property.

cache
=====

..  confval:: cache
    :name: text-cache
    :type: cache
    :parent: cobj-other

    An option that names its own parent keeps it.

Other object
============

..  confval:: OTHER
    :name: cobj-other
    :type: cObject

    Another object.

A second menu that gives ``value`` another parent:

..  confval-menu::
    :name: other-properties
    :parent: cobj-other

    ..  confval:: value
        :name: text-value
        :noindex:

        The text to output.
