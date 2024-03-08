
==========================================================
Version handling for PHP based rendering of docs.typo3.org
==========================================================

You can also require this package in your composer.json::

    composer req t3docs/typo3-version-handling

This packages uses the `phpDocumentor Guides <https://github.com/phpDocumentor/guides>`__
for parsing and rendering restructuredText.

This repository is a subtree-split of the monorepo located at
https://github.com/TYPO3-Documentation/render-guides/tree/main/packages/typo3-version-handling

This package contains a PHP enum that can be used to determine allowed TYPO3
versions for documentation interlinking. It also allows a matching between
descriptive versions like  `stable` and absolute minor versions, for
example `12.4`.

Additionally, it contains an enum with a list of supported standard inventories
of official references, tutorials and guides. These are used for interlink mapping.

More documentation about this rendering:

https://docs.typo3.org/other/t3docs/render-guides/main/en-us/Introduction/Index.html

