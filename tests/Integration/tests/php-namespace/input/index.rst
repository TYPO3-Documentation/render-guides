==============
PHP namespaces
==============

A namespace named explicitly: :php-namespace:`\TYPO3\CMS\Core\Utility`, also
without the leading backslash: :php-namespace:`TYPO3\CMS\Core`.

A namespace the API knows, named with a class role, is a namespace too:
:php:`\TYPO3\CMS\Core\Utility`, :php-short:`\TYPO3\CMS\Core\Utility`.

A class stays a class: :php:`\TYPO3\CMS\Core\Utility\GeneralUtility`.

A name that is both a class and a namespace is the class with :php: and the
namespace with :php-namespace:: :php:`\TYPO3\CMS\Core\Exception`,
:php-namespace:`\TYPO3\CMS\Core\Exception`.

An example namespace: :php-namespace:`\MyVendor\MyExtension\Controller`.

A namespace the API does not know: :php-namespace:`\Symfony\Component\Console`.

A use statement that imports a namespace does not make it a class either:

..  code-block:: php

    <?php

    use TYPO3\CMS\Extbase\Attribute as Extbase;
    use TYPO3\CMS\Extbase\DomainObject\AbstractEntity;

    final class Tea extends AbstractEntity
    {
        #[Extbase\Validate(['validator' => 'NotEmpty'])]
        protected string $title = '';
    }
