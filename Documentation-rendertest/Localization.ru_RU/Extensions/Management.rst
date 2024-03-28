.. include:: /Includes.rst.txt

.. _extensions_management:

===================
Управление расширениями
===================

Как системные расширения, так и расширения сторонних разработчиков обрабатываются с помощью Composer. Composer устанавливает расширение, а также необходимые зависимости. Composer применяется и для удаления расширений.

.. _install-extension-with-composer:

Установка расширений
=====================

Поиск имени пакета Composer для расширения
-----------------------------------------------

Зайдите в `Репозиторий расширений <https://extensions.typo3.org/>`__ и найдите расширение.

На странице расширения под :guilabel:`"Composer support"` будет указана команда Composer, необходимая для установки данного расширения.

Например, `расширение news <https://extensions.typo3.org/extension/news/>`__ имеет имя пакета `georgringer/news`.

Обычно имя пакета имеет вид vendor + слэш + ключ расширения. Однако если в ключе расширения имеется символ подчеркивания, то в имени пакета она заменяется на тире. Например:
`Extension Builder <https://extensions.typo3.org/extension/extension_builder/>`__:

* **extension key**: `extension_builder`
* **vendor**: `friendsoftypo3`
* **Composer package name**: `friendsoftypo3/extension-builder`



Для установки расширения используйте :bash:`composer require`.
-----------------------------------------------------

.. code-block:: bash
   :caption: /var/www/site/$

   composer require <packagename>

Для установки расширения news:


.. code-block:: bash
   :caption: /var/www/site/$

   composer require georgringer/news

Это добавит требование расширения в инсталляцию :file:`composer.json` и установит расширение.

Несмотря на то, что расширение устанавливается и активируется автоматически, перед использованием его необходимо настроить:

Настройка расширения
-------------------

.. code-block:: bash
   :caption: /var/www/site/$

   ./vendor/bin/typo3 extension:setup

Команда extension setup берет на себя выполнение дополнительных процедур установки, таких как миграция базы данных и очистка кэша при необходимости. Команда установки расширения не привязана к конкретному расширению, а рассматривает общее состояние и выполняет все необходимые действия.

Удаление расширений
=======================

Команда composer `remove` деинсталлирует расширение.

.. code-block:: bash
   :caption: /var/www/site/$

   composer remove georgringer/news

Обновленный файл :file:`composer.lock` должен быть зафиксирован в системе контроля версий.

.. _install_local_extensions_using_composer:

Установка локальных расширений
===========================

Локальные расширения, включая пакеты сайта и пользовательские расширения, также должны устанавливаться с помощью Composer.

Пользовательские расширения должны размещаться в специальном локальном каталоге: `documentroot/packages`.

После создания этого каталога обновите установку `composer.json` и добавьте этот каталог в качестве нового репозитория:


.. code-block:: bash
   :caption: /var/www/site/composer.json

     {
         "repositories": [
             {
                 "type": "path",
                 "url": "./packages/*/"
             },
         ],
     }

Затем можно выполнить команду `composer require` для установки локального расширения `my-local-extension` с поставщиком `vendor`:


.. code-block:: bash
   :caption: /var/www/site/$

   composer require vendor/my-local-extension:@dev

Выполняя эту команду, Composer находит папку `vendor/my-local-extension` и после выполнения команды `composer install` симлинкует ее с папкой `typo3conf/ext/my-local-extension`. Приведенная выше установка определяет, что расширение должно быть помещено composer'ом в папку `:file:packages/my-local-extension`, если оно там еще не находилось.


Дополнительная информация
======================

.. _find-out-extension-key:

Определение ключа расширения для расширения
-------------------------------------------

Для любого установленного расширения ключ расширения можно найти, заглянув в файловую систему в каталог :file:`public/typo3conf/ext/`. Имя каталога расширения совпадает с ключом расширения.

Перед установкой расширения ключ расширения можно найти на его странице в `TYPO3 Extension Repository (TER) <https://extensions.typo3.org/>`__.

Ключ расширения указан сверху. Для `расширения news <https://extensions.typo3.org/extension/news/>`__ ключом расширения является `news`.
