.. include:: /Includes.rst.txt

.. _release_integrity:

=======================
Целостность выпуска TYPO3
=======================

Релиз-пакеты TYPO3 (загружаемые tar- и zip-файлы), а также Git-теги подписываются с помощью PGP-подписей в процессе автоматизированного выпуска. Для этих файлов также генерируются хэши SHA2-256, SHA1 и MD5.

Содержание выпуска
----------------

Каждый выпуск TYPO3 поставляется со следующими файлами:

.. code-block:: bash
   :caption: `TYPO3 CMS 11.5.1 <https://get.typo3.org/version/11#accordion-heading-zip>`_ релиз в качестве примера

   typo3_src-11.5.1.tar.gz
   typo3_src-11.5.1.tar.gz.sig
   typo3_src-11.5.1.zip
   typo3_src-11.5.1.zip.sig

* ``*.tar.gz`` и ``*.zip`` файлы - это собственно релизные пакеты, в которых представлен исходный код TYPO3 CMS.
* ``*.sig`` в файлах представлены соответствующие подписи для каждого файла пакета релиза.

Проверка хэшей файлов
--------------------

Хеши файлов используются для проверки того, что загруженный файл был передан и правильно сохранен в локальной системе. В TYPO3 используются криптографические методы хэширования, включая `MD5`_ и `SHA2-256`_.

Хеши файлов для каждой версии публикуются на сайте get.typo3.org и могут быть найдены на странице соответствующего релиза, например, на https://get.typo3.org/version/11#package-checksums содержит:

.. code-block:: bash
   :caption: TYPO3 v11.5.1 Checksums
   :name: Checksums

   SHA256:
   205d1879e05c75093a2c427f7f7cacb297ca841e491450b3577987e259ff6c5b typo3_src-11.5.1.tar.gz
   e07b303405d182f4450fda4a7a7acdbe5080c22123d52f74ef5f2fbf78233a49 typo3_src-11.5.1.zip

   SHA1:
   aa88171cfb5aa9935b2a989f51e68b6d8eb6e5f0 typo3_src-11.5.1.tar.gz
   3dbe9322015e1d5266d78c6c3ff40846f8a6492f typo3_src-11.5.1.zip

   MD5:
   cda2a4494f6673e9251c265c9ef1c345 typo3_src-11.5.1.tar.gz
   252583501d30bb5679305b58ed6e6f94 typo3_src-11.5.1.zip


Для проверки хэшей файлов необходимо локально сгенерировать хэши для загружаемых пакетов и сравнить их с опубликованными хэшами на get.typo3.org. Для локальной генерации хэшей необходимо использовать один из следующих инструментов командной строки ``md5sum``, ``ha1sum`` или ``shasum``.

Следующие команды генерируют хэши для пакетов `.tar.gz` и `.zip`:

.. code-block:: bash
   :caption: ~$

   shasum -a 256 typo3_src-*.tar.gz typo3_src-*.zip
   205d1879e05c75093a2c427f7f7cacb297ca841e491450b3577987e259ff6c5b typo3_src-11.5.1.tar.gz
   e07b303405d182f4450fda4a7a7acdbe5080c22123d52f74ef5f2fbf78233a49 typo3_src-11.5.1.zip

.. code-block:: bash
   :caption: ~$

   sha1sum -c typo3_src-*.tar.gz typo3_src-*.zip
   aa88171cfb5aa9935b2a989f51e68b6d8eb6e5f0 typo3_src-11.5.1.tar.gz
   3dbe9322015e1d5266d78c6c3ff40846f8a6492f typo3_src-11.5.1.zip

.. code-block:: bash
   :caption: ~$

   md5sum typo3_src-*.tar.gz typo3_src-*.zip
   cda2a4494f6673e9251c265c9ef1c345 typo3_src-11.5.1.tar.gz
   252583501d30bb5679305b58ed6e6f94 typo3_src-11.5.1.zip

Для обеспечения целостности пакета эти хэши должны совпадать с хэшами, опубликованными на get.typo3.org.

.. _MD5: https://en.wikipedia.org/wiki/MD5
.. _SHA2-256: https://en.wikipedia.org/wiki/SHA-2


Проверка подписей файлов
------------------------

TYPO3 использует `Pretty Good Privacy`_ для подписи пакетов выпуска и тегов выпуска Git. Для проверки этих подписей рекомендуется использовать `The GNU Privacy Guard`_, однако можно также использовать любой инструмент, совместимый с `OpenPGP`_.

В релизных пакетах используется отделенная бинарная подпись. Это означает, что файл ``typo3_src-11.5.1.tar.gz`` содержит дополнительный файл подписи ``typo3_src-11.5.1.tar.gz.sig``, являющийся отсоединенной подписью.

.. code-block:: bash
   :caption: ~$

   gpg --verify typo3_src-11.5.1.tar.gz.sig typo3_src-11.5.1.tar.gz

.. code-block:: none

   gpg: Signature made Tue Oct 12 12:20:19 2021 UTC
   gpg:                using RSA key E7ED29A70309A0D1AE34DA733304BBDBFA9613D1
   gpg: Can't check signature: No public key

Предупреждение означает, что открытый ключ ``E7ED29A70309A0D1AE34DA733304BBDBFA9613D1`` еще не доступен в локальной системе и не может быть использован для проверки подписи. Открытый ключ может быть получен на любом сервере ключей - популярным является `pgpkeys.mit.edu`_.

.. code-block:: bash
   :caption: ~$

   wget -qO- https://get.typo3.org/KEYS | gpg --import

.. code-block:: none

   gpg: requesting key 59BC94C4 from hkp server pgpkeys.mit.edu
   gpg: key 59BC94C4: public key "TYPO3 Release Team (RELEASE) <typo3cms@typo3.org>" imported
   gpg: key FA9613D1: public key "Benjamin Mack <benni@typo3.org>" imported
   gpg: key 16490937: public key "Oliver Hader <oliver@typo3.org>" imported
   gpg: no ultimately trusted keys found
   gpg: Total number processed: 3
   gpg:               imported: 3  (RSA: 3)

После импорта открытого ключа можно повторить предыдущую команду по проверке подписи файла ``typo3_src-11.5.1.tar.gz``.

.. code-block:: bash
   :caption: ~$

   gpg --verify typo3_src-11.5.1.tar.gz.sig typo3_src-11.5.1.tar.gz

.. code-block:: none

   gpg: Signature made Tue Oct 12 12:20:19 2021 UTC
   gpg:                using RSA key E7ED29A70309A0D1AE34DA733304BBDBFA9613D1
   gpg: Good signature from "Benjamin Mack <benni@typo3.org>" [unknown]
   gpg: WARNING: This key is not certified with a trusted signature!
   gpg:          There is no indication that the signature belongs to the owner.
   Primary key fingerprint: E7ED 29A7 0309 A0D1 AE34  DA73 3304 BBDB FA96 13D1

Появление нового предупреждения вполне ожидаемо, постольку любой мог создать открытый ключ и загрузить его на сервер ключей. Важным моментом здесь является проверка отпечатка ключа ``E7ED 29A7 0309 A0D1 AE34 DA73 3304 BBDB FA96 13D1``, который в данном случае является правильным для пакетов выпуска TYPO3 CMS (список используемых в настоящее время ключей см. ниже или обратитесь непосредственно к файлу https://get.typo3.org/KEYS).

.. code-block:: bash
   :caption: ~$

   gpg --fingerprint E7ED29A70309A0D1AE34DA733304BBDBFA9613D1

.. code-block:: none

   pub   rsa4096 2010-06-22 [SC]
         E7ED 29A7 0309 A0D1 AE34  DA73 3304 BBDB FA96 13D1
   uid                  [ unknown] Benjamin Mack <benni@typo3.org>
   sub   rsa4096 2010-06-22 [E]

.. _Pretty Good Privacy: https://en.wikipedia.org/wiki/Pretty_Good_Privacy
.. _The GNU Privacy Guard: http://www.gnupg.org/
.. _OpenPGP: http://www.openpgp.org/
.. _pgpkeys.mit.edu: https://pgpkeys.mit.edu/


Проверка подписи тегов
----------------------

Проверка подписей на Git-тегах работает аналогично проверке результатов с помощью инструмента ``gpg``, но с использованием команды ``git tag --verify`` напрямую.

.. code-block:: bash
   :caption: ~$

   git tag --verify v11.5.1


.. code-block:: none

   object dcba2a7ce93eaef0ad025dc21fdeb85636d7b4f4
   type commit
   tag v11.5.1
   tagger Benni Mack <benni@typo3.org> 1634041135 +0200

   Release of TYPO3 11.5.1
   gpg: Signature made Tue Oct 12 14:18:55 2021 CEST
   gpg: using RSA key E7ED29A70309A0D1AE34DA733304BBDBFA9613D1
   gpg: Good signature from "Benjamin Mack <benni@typo3.org>"

Команда ``git show`` по имени тега позволяет получить более подробную информацию.

.. code-block:: bash
   :caption: ~$

   git show v11.5.1

.. code-block:: none

   tag v11.5.1
   Tagger: Benni Mack <benni@typo3.org>
   Date:   Tue Oct 12 14:17:52 2021 +0200

   Release of TYPO3 11.5.1
   -----BEGIN PGP SIGNATURE-----
   ...
   -----END PGP SIGNATURE-----



Публичные ключи
-----------

.. note::

   Начиная с июня 2017 года, релизы TYPO3 подписываются криптографической подписью ``TYPO3 Release Team'' <typo3cms@typo3.org>`` с использованием специального открытого ключа. С июля 2017 года релизы подписываются непосредственно отдельными членами команды TYPO3 Release Team, а именно ``Бенни Маком (Benni Mack) <benni@typo3.org>`` и ``Оливером Хадером (Oliver Hader) <oliver@typo3.org>``.

Используемые открытые ключи можно загрузить с сайта `get.typo3.org.keys`_

* TYPO3 Release Team <typo3cms@typo3.org>

   + 4096 bit RSA key
   + Key ID `0x9B9CB92E59BC94C4`_
   + Fingerprint ``7AF5 1AAA DED9 D002 4F89  B06B 9B9C B92E 59BC 94C4``

* Benni Mack <benni@typo3.org>

   + 4096 bit RSA key
   + Key ID `0x3304BBDBFA9613D1`_
   + Fingerprint ``E7ED 29A7 0309 A0D1 AE34  DA73 3304 BBDB FA96 13D1``

* Oliver Hader <oliver@typo3.org>

   + 4096 bit RSA key
   + Key ID `0xC19FAFD699012A5A`_, subkey of `0xA36E4D1F16490937`_
   + Fingerprint ``0C4E 4936 2CFA CA0B BFCE  5D16 A36E 4D1F 1649 0937``

...............................

.. _0x9B9CB92E59BC94C4: https://pgpkeys.mit.edu/pks/lookup?search=0x9B9CB92E59BC94C4
.. _0x3304BBDBFA9613D1: https://pgpkeys.mit.edu/pks/lookup?search=0x3304BBDBFA9613D1
.. _0xC19FAFD699012A5A: https://pgpkeys.mit.edu/pks/lookup?search=0xC19FAFD699012A5A
.. _0xA36E4D1F16490937: https://pgpkeys.mit.edu/pks/lookup?search=0xA36E4D1F16490937
.. _get.typo3.org.keys: https://get.typo3.org/KEYS
