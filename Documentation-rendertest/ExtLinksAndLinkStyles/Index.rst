.. include:: /Includes.rst.txt
.. highlight:: rst

========================
ExtLinks and Link styles
========================

.. contents:: This page
   :backlinks: top
   :class: compact-list
   :depth: 99
   :local:



ExtLinks
========

In :file:`conf.py` we have:

.. code-block:: python

   extlinks = {}
   extlinks["forge"] = ("https://forge.typo3.org/issues/%s", "Forge #")
   extlinks["issue"] = ("https://forge.typo3.org/issues/%s", "Issue #")
   extlinks["review"] = ("https://review.typo3.org/%s", "Review #")


Defined in :file:`Settings.cfg`:

.. code-block:: ini

   [extlinks]

   example1    = https://example.org/%s                    | example#
   example2    = https://example.org/%s                    | example↗
   example3    = https://example.org/%s                    | example:
   forge       = https://forge.typo3.org/issues/%s         | forge#
   issue       = https://forge.typo3.org/issues/%s"        | forge:
   packagist   = https://packagist.org/packages/%s         |
   review      = https://review.typo3.org/%s               | review:
   t3ext       = https://extensions.typo3.org/extension/%s | EXT:
   theme-issue = https://github.com/TYPO3-Documentation/sphinx_typo3_theme/issues/%s | theme#

Source::

   ===== ================================= ================================== =========================
   Line  Notation                          Alt-notation                       Result
   ===== ================================= ================================== =========================
   1     ``:example1:`dummy```             ```dummy`:example1:``              :example1:`dummy`
   2     ``:example2:`dummy```             ```dummy`:example2:``              :example2:`dummy`
   3     ``:example3:`dummy```             ```dummy`:example3:``              :example3:`dummy`
   4     ``:forge:`345```                  ```345`:forge:``                   :forge:`345`
   5     ``:issue:`12345```                ```12345`:issue:``                 :issue:`12345`
   6     ``:packagist:`georgringer/news``` ```georgringer/news`:packagist:``  :packagist:`georgringer/news`
   7     ``:review:`567```                 ```567`:review:``                  :review:`567`
   8     ``:t3ext:`news```                 ```news`:t3ext:``                  :t3ext:`news`
   9     ``:theme-issue:`21```             ```21`:theme-issue:``              :theme-issue:`21`
   ===== ================================= ================================== =========================


Rendering:

   ===== ================================= ================================== =========================
   Line  Notation                          Alt-notation                       Result
   ===== ================================= ================================== =========================
   1     ``:example1:`dummy```             ```dummy`:example1:``              :example1:`dummy`
   2     ``:example2:`dummy```             ```dummy`:example2:``              :example2:`dummy`
   3     ``:example3:`dummy```             ```dummy`:example3:``              :example3:`dummy`
   4     ``:forge:`345```                  ```345`:forge:``                   :forge:`345`
   5     ``:issue:`12345```                ```12345`:issue:``                 :issue:`12345`
   6     ``:packagist:`georgringer/news``` ```georgringer/news`:packagist:``  :packagist:`georgringer/news`
   7     ``:review:`567```                 ```567`:review:``                  :review:`567`
   8     ``:t3ext:`news```                 ```news`:t3ext:``                  :t3ext:`news`
   9     ``:theme-issue:`21```             ```21`:theme-issue:``              :theme-issue:`21`
   ===== ================================= ================================== =========================



Various
=======

Within a page
-------------

Source::

   Defining a _`target`.

Rendering:

   Defining a _`target`.

Source::

   Linking to that `target`_.

Rendering:

   Linking to that `target`_.


Other, within page
------------------

Source::

   Let's link to `various`_.

Result:

   Let's link to `various`_.




External links, outside TYPO3 universe
--------------------------------------

The domain names https://example.com, https://example.net, https://example.org,
and https://example.edu are
second-level domain names in the Domain Name System of the Internet. They are
reserved by the Internet Assigned Numbers Authority (IANA) at the direction of
the Internet Engineering Task Force (IETF) as special-use domain names for
documentation purposes.

Expected:

.. code-block:: html

   <a class="reference external" href="https://example.com" rel="nofollow noopener">https://example.com</a>
   <a class="reference external" href="https://example.net" rel="nofollow noopener">https://example.net</a>
   <a class="reference external" href="https://example.org" rel="nofollow noopener">https://example.org</a>
   <a class="reference external" href="https://example.edu" rel="nofollow noopener">https://example.edu</a>


External links, inside TYPO3 universe
-------------------------------------

*  https://typo3.org/
*  https://typo3.com/
*  https://docs.typo3.org/

Expected:

.. code-block:: html

   <a class="reference external" href="https://typo3.org/">https://typo3.org/</a>
   <a class="reference external" href="https://typo3.com/">https://typo3.com/</a>
   <a class="reference external" href="https://docs.typo3.org/">https://docs.typo3.org/</a>


