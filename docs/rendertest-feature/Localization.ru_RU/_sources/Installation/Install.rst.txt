.. include:: /Includes.rst.txt

.. index:: installation, deployment, requirements

.. _install:

================
Установка TYPO3
================

Руководство по установке TYPO3. Здесь описаны все необходимые шаги
для установки TYPO3 с помощью Composer.

Подробнее о развертывании TYPO3 в реальной среде читайте в главе :ref:`Развертывание TYPO3 <DeployTYPO3>`.

Проверка перед установкой
==========================

- Доступ к командной строке (CLI) с возможностью создания каталогов и символических ссылок.
- Доступ к `Composer <https://getcomposer.org>`__ через CLI (для локальной разработки).
- Доступ к корневой директории веб-сервера.
- База данных с соответствующими полномочиями.

Выполнить Composer Create-Project
=================================

Приведенный ниже сценарий устанавливает TYPO3 v12, которая является последней версией CMS. Если вы хотите установить версию TYPO3 с долгосрочной поддержкой (LTS), обратитесь к :ref:`TYPO3 v11 Руководство по установке <t3start11:install>`.

На корневом уровне веб-сервера выполните следующую команду:

..  tabs::

    ..  group-tab:: bash

        ..  code-block:: bash

            composer create-project typo3/cms-base-distribution example-project-directory "^12"

            # Use console command to run the install process
            # or use the Install Tool GUI (See below)
            ./vendor/bin/typo3 setup

    ..  group-tab:: powershell

        ..  code-block:: powershell

            composer create-project "typo3/cms-base-distribution:^12" example-project-directory

            # Use console command to run the install process
            # or use the Install Tool GUI (See below)
            ./vendor/bin/typo3 setup

    ..  group-tab:: ddev

        ..  code-block:: bash

            # Create a directory for your project
            mkdir example-project-directory

            # Go into that directory
            cd example-project-directory

            # Tell DDEV to create a new project of type "typo3"
            # 'docroot' MUST be 'public' PHP 8.1 is required by TYPO3 v12
            ddev config --project-type=typo3 --docroot=public --create-docroot --php-version 8.1

            # Start the server
            ddev start

            # Fetch a basic TYPO3 installation and its' dependencies
            ddev composer create "typo3/cms-base-distribution:^12"

            # Depending on your DDEV version the configuration file may have been
            # created in an outdated location, you can move it with
            mkdir -p config/system/ && mv  public/typo3conf/AdditionalConfiguration.php $_/additional.php

            # Use console command to run the install process
            # or use the Install Tool GUI (See below)
            ddev exec ./vendor/bin/typo3 setup


Эта команда получает последнюю версию TYPO3 и помещает ее в
:file:`example-project-directory`.

После выполнения этой команды, :file:`example-project-directory` будет представлена следующая структура:

.. code-block:: none

    .
    ├── .gitignore
    ├── composer.json
    ├── composer.lock
    ├── LICENSE
    ├── public
    ├── README.md
    ├── var
    └── vendor


Запуск процесса установки
=========================

Настройка TYPO3 через консоль
-----------------------------

..  versionadded:: 12.1
    Начиная с TYPO3 v12.1 в качестве альтернативы существующему веб-инсталлятору с графическим интерфейсом введена новая :ref:`CLI команда <t3coreapi:symfony-console-commands>` `setup`.

Интерактивная / управляемая установка (вопросы/ответы):

..  tabs::

    ..  group-tab:: bash

        ..  code-block:: bash

            ./vendor/bin/typo3 setup

    ..  group-tab:: powershell

        ..  code-block:: powershell

            ./vendor/bin/typo3 setup

    ..  group-tab:: ddev

        ..  code-block:: bash

            ddev exec ./vendor/bin/typo3 setup

Или используйте GUI-инсталлятор в браузере
------------------------------------------

Создайте пустой файл с названием `FIRST_INSTALL` в каталоге `/public` directory:

.. tabs::

   .. group-tab:: bash

      .. code-block:: bash

         touch example-project-directory/public/FIRST_INSTALL

   .. group-tab:: powershell

      .. code-block:: powershell

         echo $null >> public/FIRST_INSTALL

   .. group-tab:: ddev

      .. code-block:: bash

         ddev exec touch public/FIRST_INSTALL

.. code-block:: none
    .
    ├── .gitignore
    ├── composer.json
    ├── composer.lock
    ├── LICENSE
    ├── public
        ├── FIRST_INSTALL
    ├── README.md
    ├── var
    └── vendor

Доступ к TYPO3 через браузер
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

После настройки веб-сервера на директорию `public` вашего проекта, доступ к TYPO3 можно получить через веб-браузер. При первом обращении к новому сайту TYPO3 автоматически перенаправляет все запросы на :samp:`/typo3/install.php` для завершения процесса установки.

.. tip::

   При доступе к странице по протоколу HTTPS может появиться предупреждение "Ошибка конфиденциальности" или что-то подобное. В локальной среде можно игнорировать это предупреждение, указав браузеру игнорировать подобные этому предупреждения для данного домена.

   Предупреждение связано с тем, что используются самоподписанные сертификаты.

   Если при первом доступе возникает ошибка `trustedHostsPattern`, то возможен также вариант доступа к TYPO3 без (`http://`).


Сканирование среды
~~~~~~~~~~~~~~~~~~

Теперь TYPO3 просканирует среду хоста. Во время сканирования TYPO3 проверяет хост-систему на наличие следующих параметров:

- Установлена минимально необходимая версия PHP.
- Загружены необходимые расширения PHP.
- php.ini настроен.
- TYPO3 может создавать и удалять файлы и каталоги в корневом каталоге установки.

Если проблем не обнаружено, процесс установки можно продолжить.

В случае невыполнения определенных критериев TYPO3 отобразит список обнаруженных проблем, с указанием решения для каждой из них.

После внесения изменений TYPO3 может повторно просканировать среду хоста, перезагрузив страницу `https://example-project-site.local/typo3/install.php`.

.. include:: ../Images/AutomaticScreenshots/QuickInstall/Step1SystemEnvironment.rst.txt

Выбор базы данных
~~~~~~~~~~~~~~~~~

Выберите драйвер подключения к базе данных и введите учетные данные для базы данных.

.. include:: ../Images/AutomaticScreenshots/QuickInstall/Step2DatabaseConnection.rst.txt

TYPO3 может подключаться к существующей пустой базе данных или же создать новую.

Список доступных баз данных зависит от того, какие драйверы баз данных установлены на хостинге.

Например, если экземпляр TYPO3 предполагается использовать с базой данных MySQL, то необходимо установить расширение PHP 'pdo_mysql'. После его установки станет доступна опция :guilabel:`MySQL Database`.

.. include:: ../Images/AutomaticScreenshots/QuickInstall/Step3ChooseDb.rst.txt

Создание Администратора и установка названия сайта
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

Для получения доступа к внутреннему интерфейсу TYPO3 необходимо создать учетную запись администратора.

Можно также указать адрес электронной почты этого пользователя и указать его имя.

.. include:: ../Images/AutomaticScreenshots/QuickInstall/Step4AdminUserSitename.rst.txt

..  note::
    Пароль должен соответствовать заданной конфигурации :ref:`политики паролей <t3coreapi:password-policies>`.

Инициализация
-----------

TYPO3 предлагает два варианта инициализации: создание пустой стартовой страницы или переход непосредственно к внутреннему интерфейсу администратора. Новичкам лучше выбрать первый вариант и позволить TYPO3 создать пустую стартовую страницу. При этом также будет сгенерирован файл конфигурации сайта.

.. include:: ../Images/AutomaticScreenshots/QuickInstall/Step5LastStep.rst.txt

Следующие шаги
--------------

По окончании установки TYPO3 может быть :ref:`настроена <setup>`.

Использование DDEV
------------------

Кроме того, предлагается пошаговое руководство по :ref:`Установке TYPO3 с помощью DDEV <installation-ddev-tutorial>`. Учебник также содержит видеоролик.
