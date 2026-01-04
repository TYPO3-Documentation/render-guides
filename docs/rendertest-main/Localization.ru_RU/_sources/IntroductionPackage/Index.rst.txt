.. include:: /Includes.rst.txt


.. _introductionpackage_index:

====================
Ознакомительный пакет Introduction Package
====================

Если вы впервые используете TYPO3, то перед началом работы над собственным проектом вам, возможно, захочется увидеть работающий пример CMS.

Официальный ознакомительный пакет <https://extensions.typo3.org/extension/introduction/>`__ демонстрирует многие функции TYPO3 и дает возможность попробовать их в действии. В ознакомительном пакете используется расширение `bootstrap_package <https://extensions.typo3.org/extension/bootstrap_package/>`__ для создания нескольких адаптивных HTML-шаблонов, которые вы можете выбрать и опробовать.

В нем также представлены примеры различных видов содержимого страниц, которые обычно встречаются на сайте, например, абзацы текста, изображения, таблицы и навигационные меню.

.. _installing-introduction-package-with-composer:
.. _installing-distributions-wit-composer:

Установка ознакомительного пакета Introduction Package
===================================

Для установки ознакомительного пакета можно выполнить следующую команду:

.. tabs::

   .. group-tab:: bash

       .. code-block:: bash

         composer require typo3/cms-introduction

   .. group-tab:: powershell

       .. code-block:: powershell

         composer require typo3/cms-introduction

   .. group-tab:: ddev

       .. code-block:: bash

         ddev composer require typo3/cms-introduction

Эта команда загрузит и активирует расширение.

Затем выполните:

.. tabs::

   .. group-tab:: bash

       .. code-block:: bash

         vendor/bin/typo3 extension:setup

   .. group-tab:: powershell

       .. code-block:: powershell

         vendor/bin/typo3 extension:setup

   .. group-tab:: ddev

       .. code-block:: bash

         ddev typo3 extension:setup

В результате расширение будет готово к немедленному использованию.

.. _install-intro-first-steps:

Первые шаги с Introduction Package
=========================================

"Introduction Package" создает в дереве страниц несколько предустановленных страниц. Страница верхнего уровня называется "Congratulations".

.. rst-class:: bignums-xxl

#. В дереве страниц щелкните на "Congratulations".


#. Страница откроется в браузере:

   Щелкните на пиктограмме :guilabel:`"Просмотр веб-страницы"` (с глазом), чтобы просмотреть страницу в браузере.

.. include:: ../Images/AutomaticScreenshots/Frontend/IntroductionPackageHome.rst.txt


