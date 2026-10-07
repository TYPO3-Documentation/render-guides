==========
TypoScript
==========

The manual documents these itself, as the TypoScript reference does:
:typoscript:`stdWrap.parseFunc` and :typoscript:`COA_INT`.

A TCA option is no TypoScript, even at a matching path: :typoscript:`columns.label`.

Named in angle brackets, any option of the manual counts, this one too:
:typoscript:`label <columns-label>`. A name it does not document is reported:
:typoscript:`label <no-such-option>`.

A TSconfig option the manual documents, found by the path it declares:
:tsconfig:`options.pageTree.showPageIdWithTitle`.

..  _cobj-coa:
..  _cobj-coa-int:

Content object array - COA, COA_INT
===================================

..  confval:: parseFunc
    :name: stdwrap-parsefunc
    :type: object path reference / parseFunc
    :searchFacet: TypoScript

    Processing instructions to be applied to content.

..  confval:: label
    :name: columns-label
    :type: string
    :searchFacet: TCA

    The label of the field.

..  confval:: showPageIdWithTitle
    :name: useroptions-pagetree-showpageidwithtitle
    :type: boolean
    :searchFacet: TSconfig
    :User TSconfig path: options.pageTree.showPageIdWithTitle

    Shows the ID of a page next to its title in the page tree.
