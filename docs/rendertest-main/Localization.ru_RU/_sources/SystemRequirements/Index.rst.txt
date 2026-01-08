..  include:: /Includes.rst.txt

..  index:: system requirements, apache, nginx, database, mysql, sqlite

..  _system-requirements:

===================
Системные требования
===================

Для работы TYPO3 требуется веб-сервер под управлением PHP и доступ к базе данных.

Для локальной разработки понадобится Composer.

Если нужно, чтобы TYPO3 автоматически выполнял обработку изображений, например, масштабирование или обрезку, необходимо установить на сервере `GraphicsMagick (версия 1.3 или выше) <http://www.graphicsmagick.org>`__ или `ImageMagick (версия 6 или выше) <https://imagemagick.org>`__ (GraphicsMagick предпочтительнее).

Актуальную информацию о системных требованиях TYPO3 можно получить на сайте `get.typo3.org
<https://get.typo3.org/version/#system-requirements>`_.

..  include:: PHP.rst.txt

Веб сервер
==========

..  tabs::

    ..  tab:: Apache

        ..  include:: Apache.rst.txt

    ..  tab:: NGINX

        ..  include:: NGINX.rst.txt

    ..  tab:: IIS

        ..  include:: IIS.rst.txt

База данных
===========

..  include:: Database.rst.txt

Composer
========

Composer требуется только для **локальных** установок - см. `https://getcomposer.org <https://getcomposer.org>`_ для получения дополнительной информации. Рекомендуется всегда использовать последнюю доступную версию Composer. Для TYPO3 v12 LTS требуется версия Composer не ниже 2.1.0.
