=======
Confval
=======


..  confval-menu::
    :name: case-properties
    :caption: Properties of CASE
    :display: table
    :type:

    ..  _case-array:

    ..  confval:: array of cObjects
        :name: case-array
        :type: cObject

        Array of cObjects. Use this to define cObjects for the different
        values of `cobj-case-key`. If `cobj-case-key` has a certain value,
        the according cObject will be rendered. The cObjects can have any name, but not
        the names of the other properties of the cObject CASE.

    ..  _case-cache:

    ..  confval:: cache
        :name: case-cache
        :type: cache

        See  for details.

    ..  _case-default:

    ..  confval:: default
        :name: case-default
        :type: cObject

        Use this to define the rendering for *those* values of cobj-case-key that
        do *not* match any of the values of the cobj-case-array-of-cObjects. If no
        default cObject is defined, an empty string will be returned for
        the default case.

    ..  rubric:: Conditions
        :class: h2

    ..  _case-if:

    ..  confval:: if
        :name: case-if
        :type: ->if

        If if returns false, nothing is returned.
