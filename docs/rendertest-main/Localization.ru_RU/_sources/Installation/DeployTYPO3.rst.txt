.. include:: /Includes.rst.txt

.. index:: deployment, composer, production setup

.. _deploytypo3:

===============
Развертывание системы TYPO3
===============

В данном руководстве описаны шаги, необходимые для ручного развертывания TYPO3, обеспечения безопасности установки и ее готовности к использованию в производственных условиях. Также описывается ряд средств автоматизации, позволяющих упростить процесс развертывания.

Существует несколько различных способов развертывания TYPO3. Один из наиболее простых вариантов - вручную скопировать файлы и базу данных с локальной машины на живой сервер, при необходимости скорректировав конфигурацию.

Общие этапы развертывания
=========================

-  Построение локального окружения (установка всего необходимого для работы сайта).
-  Выполните команду `composer install --no-dev` для установки без зависимостей от разработки.
-  Копирование файлов на рабочий сервер.
-  Копирование базы данных на рабочий сервер.
-  Очистка кэшей.

.. note::

    Команда `composer install` не должна выполняться в реальной среде. В идеале команда `composer` должна выполняться только локально или на выделенной машине развертывания, чтобы можно было провести тестирование перед запуском в реальном режиме.

    Чтобы избежать конфликтов между локальной и серверной версиями PHP, версию PHP сервера можно определить в файле :file:`composer.json` (например, ``{"platform": {"php": "7.4.10"}}``), тогда `composer` всегда будет проверять правильность зависимостей.

Производственные настройки
==========================

Для обеспечения безопасной установки TYPO3 на рабочем сервере необходимо задать следующие параметры:

- :guilabel:`Admin Tools > Settings > Configuration Presets` Для того чтобы не выводить данные отладки на экран, необходимо выбрать предустановку "Live".
- На рабочих серверах следует использовать `HTTPS`, а для :php:`$GLOBALS['TYPO3_CONF_VARS']['BE']['lockSSL']` должно быть установлено значение `true`.
- Обеспечьте применение HSTS (Strict-Transport-Security header) в конфигурации веб-серверов.
- Переменная среды `TYPO3_CONTEXT` должна быть установлена на основной контекст `Production` (можно проверить справа вверху во внутреннем интерфейсе TYPO3 :guilabel:`Application Information`). Она должна использоваться для выбора соответствующего `базового варианта` для целевой системы в Конфигурации сайта.
- Настройте :ref:`TYPO3 logging framework <t3coreapi:logging-configuration>` на регистрацию сообщений высокой степени серьезности, включая и выше WARNING или ERROR, и продолжайте ротацию файлов журнала, хранящихся в :file:`var/log`.
- Убедитесь в том, что :ref:`разрешения на файлы <t3coreapi:security-file-directory-permissions>` правильно установлены на живой системе.

Автоматизация процесса развертывания
====================================

Типичная установка для развертывания веб-приложений состоит из трех различных частей:

- Локальная среда (для разработки).
- Среда сборки (для воспроизводимых сборок). Это может быть контролируемая локальная среда или удаленный сервер непрерывной интеграции (например, Gitlab CI или Github Actions).
- Живое (производственное) окружение.

Чтобы перевести приложение из локальной среды в производственную систему, рекомендуется использовать инструмент развертывания и/или решение непрерывной интеграции. Это гарантирует развертывание лишь кода с контролем версий и воспроизводимость сборок. В идеале развертывание нового релиза должно быть атомарной операцией и не приводить к простою. При возникновении ошибок на любом из этапов развертывания или тестирования большинство средств развертывания инициирует автоматический "откат", предотвращающий выпуск ошибочной сборки.

Одной из широко используемых стратегий является подход "переключение симлинков":

При такой стратегии веб-сервер обслуживает файлы с виртуального пути :file:`releases/current/public`, который состоит из симлинка :file:`releases/current`, указывающего на последнюю версию развертывания ("релиз"). Этот симлинк переключается после успешной подготовки нового релиза. В последней версии представлены симлинки на папки, которые должны быть общими для всех релизов (обычно их называют "общие папки" - shared folders).

Обычно база данных разделяется между релизами, а мастера обновления и обновления схем запускаются автоматически до или вскоре после запуска нового релиза.

Это примерная структура каталогов установки TYPO3 с "переключением симлинков":

.. code-block:: none

    ├── shared/
    │    ├── fileadmin/
    │    └── var/
    │        ├── var/charset/
    │        ├── var/lock/
    │        ├── var/log/
    │        └── var/session/
    ├── releases/
    │    ├── current -> ./release1 (symlink to current release)
    │    └── release1/
    │        ├── public/ (webserver root, via releases/current/public)
    │        │   ├── typo3conf/
    │        │   ├── fileadmin -> ../../../shared/fileadmin/ (symlink)
    │        │   └── index.php
    │        ├── var/
    │        |   ├── var/build/
    │        |   ├── var/cache/
    │        |   ├── var/charset -> ../../../shared/var/charset/ (symlink)
    │        |   ├── var/labels/
    │        |   ├── var/lock -> ../../../shared/var/lock/ (symlink)
    │        |   ├── var/log -> ../../../shared/var/log/ (symlink)
    │        |   └── var/session -> ../../../shared/var/session/ (symlink)
    │        ├── vendor/
    │        ├── composer.json
    │        └── composer.lock


Файлы в директории `shared` являются общими для разных версий сайта. В каталоге `releases` представлена информация о коде TYPO3, который будет меняться от версии к версии.

При использовании средств развертывания такая структура каталогов обычно создается автоматически.

В следующем разделе представлены примеры различных средств развертывания и способы их настройки для использования TYPO3:

.. tabs::

   .. tab:: TYPO3 Surf

      :doc:`TYPO3 Surf <ext_surf:Index>` это средство развертывания, написанное на языке PHP.


      .. code-block:: php
         :caption: ~/.surf/MyDeployment.php

         <?php
         /** @var \TYPO3\Surf\Domain\Model\Deployment $deployment */

         $node = new \TYPO3\Surf\Domain\Model\Node('my.node.com');
         $node
             ->setHostname($node->getName())
             ->setOption('username', 'myuser')
             ->setOption('phpBinaryPathAndFilename', '/usr/local/bin/php_cli');

         $application = new \TYPO3\Surf\Application\TYPO3\CMS();
         $application
             ->setDeploymentPath('/httpdocs')
             ->setOption('baseUrl', 'https://my.node.com/')
             ->setOption('webDirectory', 'public')
             ->setOption('symlinkDataFolders', ['fileadmin'])
             ->setOption('repositoryUrl', 'file://' . dirname(__DIR__))
             ->setOption('keepReleases', 3)
             ->setOption('composerCommandPath', 'composer')
             ->setOption('rsyncExcludes', [
                 '.ddev',
                 '.git',
                 $application->getOption('webDirectory') . '/fileadmin',
                 'packages/**.sass'
             ])
             ->setOption(TYPO3\Surf\Task\TYPO3\CMS\FlushCachesTask::class . '[arguments]', [])
             ->addSymlink($application->getOption('webDirectory') . '/config/system/settings.php', '../../../../shared/Configuration/settings.php')
             ->addNode($node);

             $deployment
                 ->addApplication($application)
                 ->onInitialize(
                     function () use ($deployment, $application) {
                         $deployment->getWorkflow()
                             ->beforeTask(\TYPO3\Surf\Task\TYPO3\CMS\SetUpExtensionsTask::class, \TYPO3\Surf\Task\TYPO3\CMS\CompareDatabaseTask::class, $application)
                             ->beforeStage('transfer', \TYPO3\Surf\Task\Php\WebOpcacheResetCreateScriptTask::class, $application)
                             ->afterStage('switch', \TYPO3\Surf\Task\Php\WebOpcacheResetExecuteTask::class, $application)
                             // CreatePackageStatesTask is done by post-autoload-dump script and can be removed
                             // https://github.com/TYPO3/TYPO3.CMS.BaseDistribution/blob/9.x/composer.json#L38
                             ->removeTask(\TYPO3\Surf\Task\TYPO3\CMS\CreatePackageStatesTask::class, $application)
                             ->removeTask(\TYPO3\Surf\Task\TYPO3\CMS\CopyConfigurationTask::class, $application);
                     }
                 );


   .. tab:: Deployer

      `Deployer <https://deployer.org>`__ представляет собой альтернативное средство развертывания написанное на языке PHP. Полное описание того, как запустить deployer для TYPO3 можно найти на сайте https://t3terminal.com/blog/typo3-deploy/

      .. code-block:: php

         <?php

         namespace Deployer;

         require_once(__DIR__ . '/vendor/sourcebroker/deployer-loader/autoload.php');
         new \SourceBroker\DeployerExtendedTypo3\Loader();

         set('repository', 'git@github.com:youraccount/yourproject.git');
         set('bin/php', '/home/www/example-project-directory/.bin/php');
         set('web_path', 'public/');

         host('live')
             ->hostname('production.example.org')
             ->user('deploy')
             ->set('branch', 'main')
             ->set('public_urls', ['https://production.example.org'])
             ->set('deploy_path', '/home/www/example-project-directory/live');


   .. tab:: Magallanes

      Еще одно средство развертывания PHP-приложений, написанных на PHP: https://www.magephp.com

      .. code-block:: yaml
         :caption: .mage.yml

         magephp:
           log_dir: ./.mage/logs
           composer:
             path: composer
           exclude:
             - ./.ddev
             - ./.git
             - ./.mage
             - ./public/fileadmin
             - ./public/typo3temp
             - ./var
             - ./.mage.yml
             - ./composer.json
             - ./composer.lock
             - ./LICENSE
             - ./README.md
           environments:
             main:
               user: ssh-user
               from: ./
               host_path: /srv/vhosts/target-path/site/mage
               releases: 3
               hosts:
                 - production.example.org
               pre-deploy:
                 - exec: { cmd: "composer install --no-dev --no-progress --optimize-autoloader"}
               on-deploy:
                 - fs/link: { from: "../../../../shared/public/fileadmin", to: "public/fileadmin" }
                 - fs/link: { from: "../../../../shared/public/typo3temp", to: "public/typo3temp" }
                 - fs/link: { from: "../../../shared/var", to: "var" }
               on-release:
               post-release:
                 - exec: { cmd: './bin/typo3 backend:lock', timeout: 9000  }
                 - exec: { cmd: './bin/typo3cms database:updateschema *.add,*.change', timeout: 9000  }
                 - exec: { cmd: './bin/typo3cms cache:flush', timeout: 9000  }
                 - exec: { cmd: './bin/typo3 upgrade:run', timeout: 9000  }
                 - exec: { cmd: './bin/typo3 backend:unlock', timeout: 9000  }
               post-deploy:
