..  include:: /Includes.rst.txt

..  _Sitemaps:

========
Sitemaps
========

Search engines and the crawlers of language models find the pages of a manual
by following its links, every old version included, unless a sitemap tells
them what there is. An official manual is rendered with a :file:`sitemap.xml`
at its root, and the docs homepage lists the sitemaps that matter in a
:file:`sitemap-index.xml`.

The sitemap of a manual
=======================

:file:`sitemap.xml` lists every page the table of contents leads to, at its
address on docs.typo3.org, with the time it was rendered as
:samp:`lastmod`. Pages no table of contents leads to, such as the 404 page,
are left out.

The address is not taken from :samp:`project-home` in :file:`guides.xml`,
which is where the manual's home is rather than where this render is
published: a permalink for the Core Changelog, Packagist for Fluid, and
:samp:`main` on the 13.4 branch of the ViewHelper Reference. It is the address
the other manuals' interlinks reach the manual at, from its interlink
shortcode and its version, such as
:samp:`https://docs.typo3.org/m/typo3/reference-tca/13.4/en-us/`.

So only the official manuals and the system extensions, which docs.typo3.org
publishes under that layout, get a sitemap. A manual of another vendor, or one
without an interlink shortcode, gets none.

The sitemap index
=================

:file:`sitemap-index.xml` is written by the docs homepage beside
:ref:`manuals.json <ManualsIndexJson>`, from the same menu. It lists the
sitemaps of each manual in the versions a search should lead to: the one in
development, the current LTS and the LTS before it, as
:file:`packages/typo3-version-handling` names them. Older versions stay
published, but are not listed.

It has a name of its own because the homepage is a manual too, with a
:file:`sitemap.xml` of its own pages.
