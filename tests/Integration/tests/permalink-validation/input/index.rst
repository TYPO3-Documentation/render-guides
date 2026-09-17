..  _permalink-validation:

====================
Permalink validation
====================

Three forms resolve while rendering and answer 404 on the published route.

A link without a manual, whose anchor is also unnormalised -- the
suggested replacement has to carry the normalised form:

`No prefix <https://docs.typo3.org/permalink/permalink_validation>`__

A link whose anchor is not in the published, normalised form:

`Underscored anchor <https://docs.typo3.org/permalink/permalinktest:permalink_validation>`__

And one written correctly, which must stay silent:

`Correct <https://docs.typo3.org/permalink/permalinktest:permalink-validation>`__

A third-party key the route reads as a different manual, because it replaces
the first hyphen with a slash:

`Interlink key <https://docs.typo3.org/permalink/friendsoftypo3/content-blocks:field-types>`__

These two resolve and must stay silent -- "typo3/…" maps to /c/, and a key
without a hyphen has nothing for the route to replace:

`Core extension <https://docs.typo3.org/permalink/typo3/cms-form:concepts-configuration>`__
`No hyphen <https://docs.typo3.org/permalink/georgringer/news:start>`__
