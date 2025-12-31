..  include:: /Includes.rst.txt

======================
Confvals with subtrees
======================

..  confval-menu::
    :name: x-case-properties
    :caption: TypoScript Case Properties
    :display: table
    :type:

    ..  confval:: array of cObjects
        :name: x-case-array
        :type: cObject
        :searchFacet: TypoScript

        Array of cObjects. Use this to define cObjects for the different
        values of `cobj-case-key`. If `cobj-case-key` has a certain value,
        the according cObject will be rendered. The cObjects can have any name, but not
        the names of the other properties of the cObject CASE.

    ..  confval:: cache
        :name: x-case-cache
        :type: cache
        :searchFacet: TypoScript

        See  for details.
