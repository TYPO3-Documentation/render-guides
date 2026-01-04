..  include:: /Includes.rst.txt

..  index:: languages, backend language

..  _backendlanguages:

======================================
Изменение языка внутреннего интерфейса
======================================

По умолчанию внутренний интерфейс TYPO3 работает на английском без каких-либо дополнительных языков.

.. contents:: **Содержание**
   :depth: 1
   :local:


Установка дополнительного языкового пакета
==========================================

Дополнительный языковой пакет может быть установлен от имени администратора во внутреннем интерфейсе:

..  rst-class:: bignums

1.  Перейдите :guilabel:`Инструменты управления > Обслуживание > Manage Languages Packs` / :guilabel:`Admin Tools > Maintenance > Manage Languages Packs`

    ..  include:: /Images/AutomaticScreenshots/Modules/ManageLanguage.rst.txt

2.  Выберете :guilabel:`Add Language` и укажите нужный язык:

    ..  include:: /Images/AutomaticScreenshots/Modules/ManageLanguagePacksAddLanguage.rst.txt

3.  После установки выбранный язык станет доступным:

    ..  include:: /Images/AutomaticScreenshots/Modules/ManageLanguagePacksAddLanguageAddSuccess.rst.txt

..  note::
    ..  versionadded:: 12.1

    Если файл :file:`config/system/settings.php` защищен от записи, все кнопки отключаются и выводится информационное окно.


Установка языка в качестве языка внутреннего интерфейса для себя
================================================================

Один из доступных языков внутреннего интерфейса может быть выбран в учетной записи пользователя. Перейдите :guilabel:`Панель инструментов (вверху справа) > Аватар пользователя > Настройки пользователя (User Settings)` и выберете нужный язык в поле :guilabel:`Язык` / :guilabel:`Language`:

..  include:: /Images/AutomaticScreenshots/BackendUsers/SettingsLanguage.rst.txt

Сохраните настройки и перезагрузите окно браузера.

..  note::
    Это изменение относится только к вашей учетной записи.


Изменение языка внутреннего интерфейса другого пользователя
===========================================================

В статусе администратора вы можете изменить язык внутреннего интерфейса другого пользователя.

..  rst-class:: bignums

1.  Перейдите :guilabel:`Система > Внутренние пользователи` / :guilabel:`System > Backend Users`

    ..  include:: /Images/AutomaticScreenshots/BackendUsers/BackendUserListing.rst.txt

2.  Выберете пользователя

3.  Измените язык

    Выберете нужный язык из установленных в системе в поле :guilabel:`Язык пользовательского интерфейса` / :guilabel:`User Interface Language`.

    ..  include:: /Images/AutomaticScreenshots/BackendUsers/SwitchUserLanguage.rst.txt
