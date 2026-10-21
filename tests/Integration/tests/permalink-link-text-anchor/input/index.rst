..  _permalink-link-text-anchor:

======================================
Permalink with the anchor as link text
======================================

A link with a single underscore names a link by its text. When that text is
the anchor, the link still goes to the anchor in this render:

`ModifyRenderedRecordEvent <https://docs.typo3.org/permalink/permalinktest:ModifyRenderedRecordEvent>`_

The same for an anchor that does not exist, which is reported:

`Missing-Anchor <https://docs.typo3.org/permalink/permalinktest:missing-anchor>`_

A link to the named link goes there, too:

`ModifyRenderedRecordEvent`_

And so does a link to a named link with another text:

`Other text <https://docs.typo3.org/permalink/permalinktest:ModifyRenderedRecordEvent>`_ and `Other text`_

..  _ModifyRenderedRecordEvent:

ModifyRenderedRecordEvent
=========================

The section that the links go to.
