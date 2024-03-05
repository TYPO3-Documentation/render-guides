
.. include:: /Includes.rst.txt


=========================
versionadded & friends
=========================

Read about the `versionadded directive`__ in the `Sphinx docs`__.

__ https://www.sphinx-doc.org/en/master/usage/restructuredtext/directives.html#directive-versionadded
__ https://www.sphinx-doc.org/en/master/

Examples
========

versionadded
   .. versionadded:: 4.5
      The *spam* parameter
   .. versionadded:: 3.1
   .. versionadded:: 2.5
      The *spam* parameter
   .. versionadded:: 2.1

versionchanged
   .. versionchanged:: 8.7

   .. versionchanged:: 6.0
      Namespaces everywhere


deprecated
   .. deprecated:: 3.1
      Use function `spam` instead.

   .. deprecated:: 2.7

The following seealso should be re-styled to a more reduced visual appearance:

.. seealso::

   Something of interest
      Visit https://typo3.org first.

   There's a company as well
      TYPO3 — the Professional, Flexible Content Management Solution

      https://typo3.com


There’s also a “short form” allowed that looks like this:

.. seealso:: https://typo3.org, https://typo3.com


