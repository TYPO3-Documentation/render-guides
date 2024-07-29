..  include:: /Includes.rst.txt

..  _php-inline:

==========
PHP Inline
==========

The hook :php:`$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['typolinkProcessing']['typolinkModifyParameterForPageLinks']`
has been removed in favor of a new PSR-14 event :php:`\TYPO3\CMS\Frontend\Event\ModifyPageLinkConfigurationEvent`.

Accessing these properties via TypoScript `getData` or via PHP will trigger a PHP :php:`E_USER_DEPRECATED` error.

In TypoScript you can access the TypoScript properties directly via
:typoscript:`.data = TSFE:config|config|fileTarget` and in PHP code via
:php:`$GLOBALS['TSFE']->config['config']['fileTarget']`.

Set it in :php:`$GLOBALS['TCA'][$table]['ctrl']['enablecolumns']`.

Some examples:

*   :php:`\TYPO3\CMS\Adminpanel\Controller\AjaxController`
*   :php:`\TYPO3\CMS\Core\Http\Dispatcher`
*   :php:`\TYPO3\CMS\Adminpanel\ModuleApi\ContentProviderInterface`
*   :php:`\TYPO3\CMS\Backend\Search\LiveSearch\SearchDemand\DemandPropertyName`
*   :php:`\TYPO3\CMS\Backend\Form\Behavior\OnFieldChangeTrait`
*   :php:`\Psr\Log\LoggerInterface`
*   :php:`\TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper`
*   :php:`\MyVendor\MyExtension\FooBar`
*   :php:`\Foo\Bar\Something`

In short:

*   :php-short:`\TYPO3\CMS\Adminpanel\Controller\AjaxController`
*   :php-short:`\TYPO3\CMS\Core\Http\Dispatcher`
*   :php-short:`\TYPO3\CMS\Adminpanel\ModuleApi\ContentProviderInterface`
*   :php-short:`\TYPO3\CMS\Backend\Search\LiveSearch\SearchDemand\DemandPropertyName`
*   :php-short:`\TYPO3\CMS\Backend\Form\Behavior\OnFieldChangeTrait`
*   :php-short:`\Psr\Log\LoggerInterface`
*   :php-short:`\TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper`
*   :php-short:`\MyVendor\MyExtension\FooBar`
*   :php-short:`\Foo\Bar\Something`
