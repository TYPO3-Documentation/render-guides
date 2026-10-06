==================================
Language of a literal include
==================================

..  literalinclude:: _Snippets/_script.sh

..  literalinclude:: _Snippets/_query.sql

..  literalinclude:: _Snippets/_locallang.xlf

..  literalinclude:: _Snippets/_.htaccess

..  literalinclude:: _Snippets/.env

..  literalinclude:: _Snippets/_Dockerfile-apache-php

..  literalinclude:: _Snippets/_Makefile

..  literalinclude:: _Snippets/_Example.rst

..  literalinclude:: _Snippets/_Settings.YAML

An explicit language wins over the extension:

..  literalinclude:: _Snippets/_script.sh
    :language: plaintext

A file that tells nothing about its language is plain text, with a warning:

..  literalinclude:: _Snippets/_notes.unknown
