==============
Tabs
==============

..  tabs::

    ..  group-tab:: bash

        ..  code-block:: bash

            composer create-project typo3/cms-base-distribution:^11 example-project-directory

    ..  group-tab:: powershell

        ..  code-block:: powershell

            composer create-project "typo3/cms-base-distribution:^11" example-project-directory

..  tabs::

    ..  group-tab:: bash

        ..  code-block:: bash

            touch example-project-directory/public/FIRST_INSTALL

    ..  group-tab:: powershell

        ..  code-block:: powershell

            echo $null >> public/FIRST_INSTALL
