=======
Confval
=======

Confval menu as list
====================

..  confval-menu::
    :name: confval-list

Confval menu as list with more info
===================================

..  confval-menu::
    :caption: Confval menu as list with more info
    :name: confval-list-info
    :type:
    :default:

Confval menu as tree
====================

..  confval-menu::
    :display: tree
    :name: confval-tree

Confval menu as tree with more info
===================================

..  confval-menu::
    :caption: Confval menu as tree with more info
    :name: confval-tree-info
    :display: tree
    :type:
    :default:

Confval menu as table
=====================

..  confval-menu::
    :name: confval-table
    :display: table
    :type:
    :default:
    :Level:
    :exclude: exclude in table


Confvals
========

..  confval:: demo 1
    :type: string
    :default: ``"Hello World"``
    :required: False
    :Level: 1

    Some Text


..  confval:: demo 2
    :type: string
    :default: ``"Hello World"``
    :required: False
    :Level: 1

    Some Text


    ..  confval:: demo 2.1
        :type: string
        :default: ``"Hello World"``
        :required: False
        :Level: 2

        Some Text

        ..  confval:: demo 2.1.1
            :type: string
            :default: ``"Hello World"``
            :required: False
            :Level: 3

            Some Text

        ..  confval:: demo 2.1.2
            :type: string
            :default: ``"Hello World"``
            :required: False
            :Level: 3

            Some Text

    ..  confval:: demo 2.2
        :type: string
        :default: ``"Hello World"``
        :required: False
        :Level: 2

        Some Text

..  confval:: demo 3
    :type: string
    :default: ``"Hello World"``
    :required: False
    :Level: 1

    Some Text

..  confval:: demo 4
    :type: string
    :default: ``"Hello World"``
    :required: False
    :Level: 1

    Some Text

..  confval:: exclude in table
    :type: string
    :default: ``"Hello World"``
    :required: False
    :Level: 1

    Some Text
