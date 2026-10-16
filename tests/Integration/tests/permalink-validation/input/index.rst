..  _permalink-validation:

====================
Permalink validation
====================

Some forms resolve while rendering and answer 404 on the published route.

A link without a manual, whose anchor is also unnormalised -- the
suggested replacement has to carry the normalised form:

`No prefix <https://docs.typo3.org/permalink/permalink_validation>`__

A link whose anchor is not in the published, normalised form:

`Underscored anchor <https://docs.typo3.org/permalink/permalinktest:permalink_validation>`__

And one written correctly, which must stay silent:

`Correct <https://docs.typo3.org/permalink/permalinktest:permalink-validation>`__

Manual keys with a slash resolve and must stay silent: the route uses
them as written, also with a hyphen in the package or the vendor name.
"typo3/…" maps to /c/:

`Interlink key <https://docs.typo3.org/permalink/friendsoftypo3/content-blocks:field-types>`__
`Vendor with a hyphen <https://docs.typo3.org/permalink/web-vision/deepltranslate-core:start>`__
`Core extension <https://docs.typo3.org/permalink/typo3/cms-form:concepts-configuration>`__
`No hyphen <https://docs.typo3.org/permalink/georgringer/news:start>`__

A file path where the anchor belongs, as the link modal used to offer for a
headline without an anchor. No spelling of the path resolves, so the warning
must not suggest one:

`File path <https://docs.typo3.org/permalink/permalinktest:Index#permalink-validation>`__
`Nested file path <https://docs.typo3.org/permalink/permalinktest:Sub/Index>`__
