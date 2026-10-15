..  include:: /Includes.rst.txt
..  _api:

=========
TYPO3 API
=========

*   :api-class:`\TYPO3\CMS\Extbase\Routing\ExtbasePluginEnhancer`
*   :api-class:`In main <api-dev:\TYPO3\CMS\Extbase\Routing\ExtbasePluginEnhancer>`
*   :api-class:`In 11.5 <api-11:\TYPO3\CMS\Extbase\Routing\ExtbasePluginEnhancer>`
*   :api-class:`\TYPO3\CMS\Core\EventDispatcher\EventDispatcher <api:\TYPO3\CMS\Core\EventDispatcher\EventDispatcher>`

..  _api-class-directive:

Show a class with typo3:api-class
=================================

The directive loads the class from api.typo3.org, for the version that
`typo3-core-preferred` in :file:`guides.xml` names.

..  _api-class-directive-event:

A PSR-14 event
--------------

..  code-block:: rst

    ..  typo3:api-class:: \TYPO3\CMS\Impexp\Event\BeforeImportEvent

..  typo3:api-class:: \TYPO3\CMS\Impexp\Event\BeforeImportEvent

..  _api-class-directive-members:

Selected members of a class
---------------------------

..  code-block:: rst

    ..  typo3:api-class:: \TYPO3\CMS\Core\Site\Entity\Site
        :members: getIdentifier, getBase, getLanguages, getLanguageById
        :no-class-comment:

..  typo3:api-class:: \TYPO3\CMS\Core\Site\Entity\Site
    :members: getIdentifier, getBase, getLanguages, getLanguageById
    :no-class-comment:

..  _api-class-directive-enum:

An enum
-------

..  code-block:: rst

    ..  typo3:api-class:: \TYPO3\CMS\Core\Utility\DiffGranularity

..  typo3:api-class:: \TYPO3\CMS\Core\Utility\DiffGranularity
