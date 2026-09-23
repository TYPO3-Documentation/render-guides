..  _code-folding:

============
Code folding
============

The header of a PHP file is folded:

..  literalinclude:: _codesnippets/_MyListener.php
    :caption: EXT:my_extension/Classes/EventListener/MyListener.php

Only the lines an author names are shown; a comment spans the fold:

..  literalinclude:: _codesnippets/_MyListener.php
    :caption: EXT:my_extension/Classes/EventListener/MyListener.php
    :visible-lines: 17-21, 24-25

Folding switched off:

..  literalinclude:: _codesnippets/_MyListener.php
    :visible-lines: all

An invalid value is reported and leaves the default folding:

..  literalinclude:: _codesnippets/_MyListener.php
    :visible-lines: from here

A file with no header worth folding:

..  literalinclude:: _codesnippets/_Short.php

A code-block takes the option too:

..  code-block:: yaml
    :visible-lines: 2

    services:
      MyVendor\MyExtension\:
        resource: '../Classes/*'

With line numbers and emphasized lines:

..  literalinclude:: _codesnippets/_MyListener.php
    :linenos:
    :emphasize-lines: 20
