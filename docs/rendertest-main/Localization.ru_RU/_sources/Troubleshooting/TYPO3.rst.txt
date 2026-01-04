.. include:: /Includes.rst.txt

.. index:: resetting password, new password, new user

.. _troubleshooting_typo3:

=====
TYPO3
=====

Сброс паролей
===================

.. _backend-admin-password:

Пароль администратора внутреннего интерфейса
------------------------------

При необходимости сброса пароля пользователя внутреннего интерфейса войдите в него под другим пользователем и воспользуйтесь инструментом :guilabel:`System > Backend Users` для сброса пароля пользователя. Обратите внимание, что только пользователи внутреннего интерфейса с правами администратора могут получить доступ к инструменту `Backend Users` для внесения этих изменений.

Если альтернативная учетная запись администратора недоступна или она не имеет соответствующего доступа, то для создания нового административного пользователя можно напрямую обратиться к программе Install Tool, указав следующий адрес:

.. code-block:: none

   https://example.com/typo3/install.php

Инструмент установки требует ввода "Пароля установки", который был задан при установке TYPO3.

.. include:: ../Images/AutomaticScreenshots/InstallTool/InstallToolPassword.rst.txt

После входа в систему Admin Tool перейдите в раздел :guilabel:`Maintenance > Create Administrative User` и выберите :guilabel:`Create Administrator`. В этом диалоге вы можете создать нового административного пользователя.

.. include:: ../Images/AutomaticScreenshots/BackendUsers/CreateAdministrator.rst.txt

.. include:: ../Images/AutomaticScreenshots/BackendUsers/CreateAdministratorForm.rst.txt

Используйте эту новую учетную запись администратора для входа во внутренний интерфейс TYPO3. В модуле :guilabel:`Внутренние пользователи` / :guilabel:`Backend Users` можно изменить пароли существующих пользователей, включая администраторов.

.. _install-tool-password:

Пароль программы установки Install Tool
---------------------

Для сброса пароля :guilabel:`Install Tool` требуется доступ на запись в :file:`config/system/settings.php` (в традиционных устаревших установках :file:`typo3conf/system/settings.php`).

Перед редактированием этого файла обратитесь к разделу:

.. code-block:: none

   https://example.com/typo3/install.php


Введите новый пароль в диалоговое окно. Поскольку новый пароль не верен, будет получен следующий ответ:

.. code-block:: none
   :caption: Example Output

   "Given password does not match the install tool login password. Calculated hash:
   $argon2i$v=xyz"

Скопируйте этот хэш, включая часть :php:`$argon2i` и все завершающие точки.

Затем отредактируйте файл :`config/system/settings.php` и замените следующую запись массива на новый хэшированный пароль:

.. code-block:: php
   :caption: config/system/settings.php

   'BE' => [
      'installToolPassword' => '$argon2i$v=xyz',
   ],

..  note::

    Если новый пароль не работает, проверьте, не переопределяется ли он в дальнейшем в файле :file:`config/system/settings.php` или в файле :file:`config/system/additional.php`, если таковой существует. Если войти в программу установки по-прежнему не удается, проверьте, нет ли ошибок в журналах при включенной отладке.

.. _debug-mode:

Настройки отладки
==============

При устранении неполадок в разделе :guilabel:`"Settings > Configuration Presets"` инструмента установки, в разделе "Debug settings", можно изменить предустановку "Debug" для отображения ошибок во фронтенде.

.. include:: ../Images/AutomaticScreenshots/DebugSettings/ConfigurationPresets.rst.txt

.. include:: ../Images/AutomaticScreenshots/DebugSettings/DebugSettings.rst.txt

В корневой шаблон сайта также можно добавить следующий параметр TypoScript для отображения дополнительной отладочной информации. Это особенно полезно при отладке ошибок Fluid:

.. code-block:: typoscript

   config.contentObjectExceptionHandler = 0

.. seealso::

   :ref:`t3coreapi:error-handling-configuration-examples-debug`

.. important::

   После завершения отладки обязательно удалите все отладочные Typoscript и верните настройку Отладка / Debug в положение 'Live'.

Кроме того, для получения дополнительной информации следует проверить следующие протоколы:

*  Файлы журналов веб-сервера для выявления общих проблем (например, :file:`/var/log/apache2` или :file:`/var/log/httpd` в системах на базе Linux).
*  Вход в систему администрирования TYPO3 :guilabel:`SYSTEM > Log` через внутренний интерфейс TYPO3.
*  Журналы TYPO3, записываемые :ref:`Logging Framework <t3coreapi:logging>`, располагаются в :file:`var/log` или :file:`typo3temp/var/log` в зависимости от настроек установки.

.. _troubleshooting-caching:

Кэширование
=======

Cached Files in typo3temp/
--------------------------

TYPO3 создает временные "кэшированные" файлы и PHP-скрипты в каталоге :file:`<var-path>/cache/` (либо :file:`typo3temp/var/cache`, либо :file:`var/cache` в зависимости от установки). В любой момент можно удалить весь каталог :file:`<var-path>/cache`, при этом структура каталога и все кэши будут перезаписаны при следующем обращении посетителя к сайту.

Ярлык для удаления этих кэшей можно найти в :guilabel:`Install Tool`, в разделе :guilabel:`Important Actions`. Это может быть полезно в том случае, если файлы кэша повреждены и выполнение системы невозможно. Инструмент установки не будет загружать ни один из этих кэшей или расширений, поэтому его можно использовать независимо от поврежденного состояния кэшей.

Среди прочих кэшей в разделе :file:`<var-path>/cache/code/core/` находится:

.. code-block:: bash
   :caption: <var-path>/cache/code/core/

   -rw-rw----   1 www-data   www-data   61555  2014-03-26 16:28   ext_localconf_8b0519db6112697cceedb50296df89b0ce04ff70.php
   -rw-rw----   1 www-data   www-data   81995  2014-03-26 16:28   ext_tables_c3638687920118a92ab652cbf23a9ca69d4a6469.php

В этих файлах представлены все файлы :file:`ext\_tables.php` и :file:`ext\_localconf.php` установленных расширений, скомпонованные в порядке их загрузки. Поэтому включение одного из этих файлов равносильно включению потенциально сотен PHP-файлов и должно повысить производительность.



.. _possible-problems-with-the-cached-files:

Возможные проблемы с кэшируемыми файлами
---------------------------------------

.. _changing-the-absolute-path-to-typo3:

Изменение абсолютного пути к TYPO3
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

Если изменить путь установки TYPO3, то могут возникнуть аналогичные ошибки, в том числе "Failed opening ..." или "Unable to access ...". Проблема заключается в том, что абсолютные пути к файлам жестко закодированы внутри кэшированных файлов.

Решение: очистите кэш с помощью Install Tool: Перейдите в раздел "Важные действия" / "Important Actions" и воспользуйтесь функцией "Очистить все кэши" / "Clear all caches". Затем снова откройте страницу.


.. _changing-image-processing-settings:

Изменение настроек обработки изображений
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

При изменении настроек обработки изображений (в обычном режиме) необходимо учитывать, что в папке :file:`typo3temp/` все еще могут находиться старые изображения, которые препятствуют генерации новых файлов! Это особенно важно знать, если вы впервые пытаетесь настроить обработку изображений.

Проблема решается очисткой файлов в папке :file:`typo3temp/`. Также не забудьте очистить таблицу базы данных "cache\_pages".
