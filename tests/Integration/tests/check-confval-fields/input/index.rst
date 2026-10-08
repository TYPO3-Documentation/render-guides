==============
Confval fields
==============

..  confval-menu::
    :display: table
    :type:
    :TCA path:
    :Path:

    ..  confval:: size
        :name: size
        :type: integer
        :default: 30
        :TCA path: $GLOBALS['TCA'][$table]['columns'][$field]['config']
        :Scope: Display
        :searchFacet: TCA

        Declared fields, the directive's own and the theme's: no warning.

    ..  confval:: cols
        :name: cols
        :Default: 30
        :TCA Path: $GLOBALS['TCA'][$table]['columns'][$field]['config']
        :Remark: Not a field of this manual

        A built-in field with the wrong case, a declared one with the wrong
        case, and one that is not declared at all.

    ..  confval:: rows
        :name: rows
        :added: 12.4
        :changed: 13.0
        :Deprecated: 14.0

        The version fields the theme reads: no warning, unless spelled
        differently.
