==========================
With language "typoscript"
==========================

Imports
=======

..  code-block:: typoscript

    @import 'EXT:myproject/Configuration/TypoScript/*.typoscript'
    @import './userIsLoggedIn.typoscript'
    @import "./subDirectory/*.setup.typoscript"

    <INCLUDE_TYPOSCRIPT: source="FILE:EXT:my_extension/Configuration/TypoScript/myMenu.typoscript">
    <INCLUDE_TYPOSCRIPT: source="DIR:fileadmin/templates/" extensions="typoscript">
    <INCLUDE_TYPOSCRIPT: source="FILE:EXT:my_extension/Configuration/TypoScript/user.typoscript" condition="[frontend.user.isLoggedIn]">


Conditions
==========

..  code-block:: typoscript

    [date("j") == 9]
        # ...
    [END]

    [date("j") == {$foo.bar}]
        # ...
    [ELSE]
        # ...
    [GLOBAL]


Quoting of SQL identifiers
==========================

..  code-block:: typoscript

    select.where = ({#title} LIKE {#%SOMETHING%} AND NOT {#doktype})


Variables
=========

..  code-block:: typoscript

    something = {$foo.bar}


Comments
========

..  code-block:: typoscript

    # Some comment

    something = 42 # Some comment

    // Another comment

    something = 42 // Another comment

    /* Some
    multiline
    comment */

    something = 42 /* some
    multiline
    comment */ and more


Register
========

..  code-block:: typoscript

    dataWrap = <ul class="{register:ulClass}">|</ul>


Colors
======

..  code-block:: typoscript

    something = #123

    something = #abcdef

    something = 1234 # not recognized as color


Array numbers
=============

..  code-block:: typoscript

    10 = TEXT
    10.value = something

    10 = TEXT
    10 {
        value = something
    }


Keywords
========

..  code-block:: typoscript

    10 = TEXT

    10 = GIFBUILDER

    10 = TMENU

    10 = NO
