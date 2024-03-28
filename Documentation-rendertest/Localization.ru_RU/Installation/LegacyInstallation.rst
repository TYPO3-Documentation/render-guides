.. include:: /Includes.rst.txt

.. index:: legacy installation

.. _legacyinstallation:

===================
Традиционная установка
===================

В данном руководстве подробно описано, как можно установить TYPO3 без использования Composer. Этот способ установки в настоящее время считается устаревшим, пользователям настоятельно рекомендуется использовать установку с помощью Composer :ref:`install`.

Установка на Unix-сервер
===========================

#. Загрузите исходный пакет TYPO3 с сайта `https://get.typo3.org/
   <https://get.typo3.org/>`_:

   .. code-block:: bash
      :caption: /var/www/site/$

      wget --content-disposition https://get.typo3.org/11

   Убедитесь, что пакет находится на один уровень выше корня документа веб-сервера.

#. Распакуйте :file:`typo3_src-12.4.x.tar.gz`:

   .. code-block:: bash
      :caption: /var/www/site/$

      tar xzf typo3_src-12.4.x.tar.gz

   Обратите внимание, что `x` в извлеченной папке будет заменен на последнюю обновленную версию TYPO3.


#. Создайте в корне документа следующие симлинки:


   .. code-block:: bash
      :caption: /var/www/site/$

      cd public
      ln -s ../typo3_src-12.4.x typo3_src
      ln -s typo3_src/index.php index.php
      ln -s typo3_src/typo3 typo3

.. important::
   Обязательно загрузите весь каталог с исходным кодом TYPO3, включая каталог :file:`vendor`, иначе вы упустите важные зависимости.

#. В результате образуется следующая структура:

   .. code-block:: none

    ├── typo3_src-12.4.x/
    ├── public/
    ├── ── typo3_src -> ../typo3_src-12.4.x/
    ├── ── typo3 -> typo3_src/typo3/
    ├── ── index.php -> typo3_src/index.php

Установка на сервер Windows
==============================

#. Загрузите исходный пакет TYPO3 с сайта `https://get.typo3.org/ <https://get.typo3.org/>`_ и распакуйте файл :file:`.zip` на веб-сервере.

   Убедитесь, что пакет находится на один уровень выше корня документа веб-сервера.


#. С помощью оболочки создайте в корне документа следующие симлинки:

   .. code-block:: bash
      :caption: /var/www/site/$

      cd public
      mklink /d typo3_src ..\typo3_src-12.4.x
      mklink /d typo3 typo3_src\typo3
      mklink index.php typo3_src\index.php

#. В результате образуется следующая структура:

   .. code-block:: none

    ├── typo3_src-12.4.x/
    ├── public/
    ├── ── typo3_src -> ../typo3_src-12.4.x/
    ├── ── typo3 -> typo3_src/typo3/
    ├── ── index.php -> typo3_src/index.php


Завершение установки
===========================

После извлечения исходного пакета и создания симлинков перейдите на страницу Access TYPO3 через веб-браузер для завершения установки.
