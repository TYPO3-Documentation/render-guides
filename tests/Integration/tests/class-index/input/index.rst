..  _class-index:

===========
Class index
===========

The :php:`\TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance()` method is
named here, and :php-short:`\TYPO3\CMS\Core\Http\ServerRequest` as well.

..  _class-index-example:

An example
==========

:php:`\TYPO3\CMS\Core\Utility\GeneralUtility` again, in another section.

..  code-block:: php

    <?php
    use TYPO3\CMS\Core\Http\ServerRequest;
    use TYPO3\CMS\Core\Utility\{GeneralUtility, PathUtility};
    use Psr\Log\LoggerInterface as Logger;
    use function TYPO3\CMS\Core\Utility\helper;
    use SomeTraitOfTheExample;

    final class Example
    {
        use SomeTraitOfTheExample;
    }

A section without a label
=========================

A section without a label of its own is named beside the nearest label above
it. Naming two members of a class here, :php:`\TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance()`
and :php:`\TYPO3\CMS\Core\Utility\GeneralUtility::getFileAbsFileName()`, makes one place
that lists both.

A class this manual documents
=============================

..  php:class:: \Acme\Shop\Service\Greeter

    Greets.

Example classes are left out
============================

A class below :php:`\MyVendor\MyExtension\FooBar`, :php:`\Vendor\Ext\Thing`
or :php:`\Foo\Bar\Baz` stands for the reader's own and exists nowhere, so the
index passes it over, here and in an example:

..  code-block:: php

    <?php
    use MyVendor\MyExtension\Service\Greeter;
    use Vendor\Ext\Service;
    use Foo\Bar\Baz;
