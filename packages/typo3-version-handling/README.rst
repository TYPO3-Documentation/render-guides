
==========================================================
Version handling for PHP based rendering of docs.typo3.org
==========================================================

This package contains a PHP enum that can be used to determine allowed TYPO3
versions for documentation interlinking. It also allows a matching between
relative versions, for example `stable` and absolute minor versions, for
example `12.4`.

Additionally it contains an enum with a list of supported standard inventories
of official manuals. These are used for interlink mapping.

You can also require this package in your composer.json::

    composer req t3docs/typo3-version-handling

