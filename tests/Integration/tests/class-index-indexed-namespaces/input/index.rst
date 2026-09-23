..  _indexed-namespaces:

==================
Indexed namespaces
==================

This manual keeps the classes of :php:`\TYPO3\CMS` alone. So
:php:`\TYPO3\CMS\Core\Utility\GeneralUtility` is indexed, while
:php:`\Psr\Log\LoggerInterface` and :php:`\TYPO3Fluid\Fluid\View\TemplateView`
are not, in the text and in an example alike:

..  code-block:: php

    <?php
    use TYPO3\CMS\Core\Http\ServerRequest;
    use Psr\Http\Message\ResponseInterface;
