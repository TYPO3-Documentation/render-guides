==================
TypoScript options
==================

An option by its full path: :typoscript:`stdWrap.parseFunc`, and a property
of the top-level object: :typoscript:`page.includeJS`.

An option name alone stays code, because the reference documents several
options of that name: :typoscript:`wrap`.

A path the reference does not document stays code: :typoscript:`lib.myContent.wrap`.

An option of Extbase plugins, below the key of a plugin:
:typoscript:`plugin.tx_blog.persistence.storagePid`.

An object type the reference documents by a headline only, as an older
version of it does, links there without a description. Found by its anchor,
where a content object that is also a GIFBUILDER object is the content
object: :typoscript:`IMAGE`. Found by a page of its own: :typoscript:`EMBOSS`.

An object type the reference no longer documents stays code:
:typoscript:`FILE`, and so does a word that is no object type:
:typoscript:`GET`.

TypoScript that names neither: :typoscript:`page = PAGE`.

Documented as options of their own
==================================

An object type the reference documents as an option of its own says what it
does: :typoscript:`USER`, :typoscript:`COA_INT`, :typoscript:`PAGEVIEW`,
:typoscript:`PAGE`. So does a
function named alone: :typoscript:`stdWrap`, :typoscript:`typolink`.

A function whose anchor a property already had, and one written in mixed case:
:typoscript:`HTMLparser_tags`, :typoscript:`HTMLparser`. A top-level object:
:typoscript:`config`.

The object of ``page = PAGE`` is not the type: :typoscript:`page`. A name of
several options stays code: :typoscript:`wrap`.

Named in angle brackets
=======================

Where the code alone does not tell the option, a role names its confval as
``:confval:`` does, in this manual or another: :typoscript:`current <t3tsref:stdwrap-current>`,
:typoscript:`config.tx_extbase <t3coreapi:extbase-typoscript-config-tx-extbase>`.
Any kind of option counts, not only TypoScript ones.

A key that starts with an underscore can name its confval too:
:typoscript:`_CSS_DEFAULT_STYLE <t3tsref:plugin-css-default-style>`.

A name the manual does not document is reported: :typoscript:`wrap <t3tsref:no-such-option>`.
A manual that cannot be reached is not: :typoscript:`wrap <t3unreachable:some-option>`.

In a definition list
====================

A role in the term of a definition list is described as in running text.

:typoscript:`current <t3tsref:stdwrap-current>`
    Named in angle brackets.

:typoscript:`stdWrap.parseFunc`
    By its full path.

*Emphasised* :typoscript:`COA_INT`
    An object type after other inline text.

TSconfig
========

A TSconfig option by its full path: :tsconfig:`mod.web_list.itemsLimitSingleTable`.

A TSconfig option whose anchor does not follow its path, found by the path it
declares: :tsconfig:`options.pageTree.doktypesToShowInNewPageDragArea`. The
same with :typoscript:`options.pageTree.doktypesToShowInNewPageDragArea`.

An option with a placeholder in its path is found as the placeholder is
written: :tsconfig:`mod.wizards.newContentElement.wizardItems.group.before`,
but not with a name in its place:
:tsconfig:`mod.wizards.newContentElement.wizardItems.common.before`.

TSconfig the reference does not document stays code: :tsconfig:`mod.web_list.noViewWithDokTypes`.
