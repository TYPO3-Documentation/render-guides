..  include:: /Includes.rst.txt

.. _start:

=============
Render guides
=============

:Package name:
    render-guides

:Version:
    |release|

:Language:
    en

:Author:
    TYPO3 contributors

:License:
    This document is published under the
    `Creative Commons BY 4.0 <https://creativecommons.org/licenses/by/4.0/>`__
    license.

:Rendered:
    |today|

----

This guide contains information about the render-guides tool and how to use it
as a documentation writer. It also contains information for developers who want
to extend the tool.

----

**Table of Contents:**

..  toctree::
    :maxdepth: 2
    :titlesonly:

    Introduction/Index
    Installation/Index
    Developer/Index
    Migration/Index
    KnownProblems/Index

..  uml::

    class Foo1 {
        You can use
        several lines
        ..
        as you want
        and group
        ==
        things together.
        __
        You can have as many groups
        as you want
        --
        End of class
    }

    class User {
        ..  Simple Getter ..
        + getName()
        + getAddress()
        ..  Some setter ..
        + setName()
        __ private data __
        int age
        -- encrypted --
        String password
        }
