.. include:: /Includes.rst.txt

============
Replacements
============

.. contents:: This page
   :backlinks: top
   :class: compact-list
   :depth: 99
   :local:


.. attention::

   As of today |today|, use this DRC to render this page::

      docker pull ghcr.io/t3docs/render-documentation:develop
      docker tag ghcr.io/t3docs/render-documentation:develop t3docs/render-documentation:develop
      eval "$(docker run --rm t3docs/render-documentation:develop show-shell-commands)"
      dockrun_t3rd makehtml-no-cache


Available replacements
======================

Overview
--------

======================  =====  ===========================  =======================  =====================  =========
Meaning                 Used   Markup                       Importance               Name                   Prefix
                        by
                        theme
======================  =====  ===========================  =======================  =====================  =========
Audience                       `|dt3m_audience|`            desired                  `audience`             `dt3m_`
Author                         `|std_author|`               expected                 `author`               `std_`
Contact                 yes    `|hto_project_contact|`      optional                 `project_contact`      `hto_`
Copyright               yes    `|std_copyright|`            expected                 `copyright`            `std_`
Description                    `|dt3m_description|`         recommended              `description`          `dt3m_`
Discussions             yes    `|hto_project_discussions|`  optional                 `project_discussions`  `hto_`
Doctype                        `|dt3m_doctype|`             desired                  `doctype`              `dt3m_`
Home                    yes    `|hto_project_home|`         optional                 `project_home`         `hto_`
Issues                  yes    `|hto_project_issues|`       optional                 `project_issues`       `hto_`
Language (any text)            `|dt3m_language|`            optional                 `language`             `dt3m_`
Language (letter code)  yes    `|std_language|`             required if not `en`     `language`             `std_`
License                        `|dt3m_license|`             desired                  `license`              `dt3m_`
Maintainer                     `|dt3m_maintainer|`          optional                 `maintainer`           `dt3m_`
Project                 yes    `|std_project|`              required                 `project`              `std_`
Release                        `|std_release|`              is alias                 `release`              `std_`
Release                 yes    `|release|`                  required                 `release`
Rendered                       `|today|`                    is read only             `today`
Repository              yes    `|hto_project_repository|`   optional                 `project_repository`   `hto_`
Version                        `|std_version|`              is alias                 `version`              `std_`
Version                 yes    `|version|`                  required                 `version`              `version`
Website                        `|dt3m_website|`             optional                 `website`              `dt3m_`
======================  =====  ===========================  =======================  =====================  =========


If present, the following settings are used to create an "Edit" button.

======================  ===========================  =======================
Name                    Markup                       Example
======================  ===========================  =======================
edit_button_type        `|hto_edit_button_type|`     |hto_edit_button_type|
repo_file_edit_url      `|hto_repo_file_edit_url|`   |hto_repo_file_edit_url|
======================  ===========================  =======================


Meaning of prefixes
-------------------

.. confval::                  std_

   :Spoken as:                standard
   :Section in Settings.cfg:  [general]
   :Assignment in conf.py:    `name = "text"`
   :Provider:                 built in, comes with Sphinx


.. confval::                  hto_

   :Spoken as:                html_theme_options
   :Section in Settings.cfg:  [html_theme_options]
   :Assignment in conf.py:    `html_theme_options["name"] = "text"`
   :Provider:                 built in, comes with Sphinx


.. confval::                  dt3m_

   :Spoken as:                docs typo 3 meta
   :Section in Settings.cfg:  [docstypo3-meta]
   :Assignment in conf.py:    `docstypo3["meta"]["name"] = "text"`
   :Provider:                 extension `sphinxcontrib-docstypo3
                              <https://github.com/TYPO3-Documentation/sphinxcontrib-docstypo3>`__

Optional postfixes
------------------

You *may* append a postfix to the replacement key to specify through which
function the replacement text should go.

.. confval:: _json

   'json' means that the replacement text should be valid json code.

.. confval:: _r

   'r' signals that you want a 'representation' of the text, not just the text
   itself. A represention is a string that can be evaluated (by Python)
   and results in the original string.

.. confval:: _linked

   Urls and email addresses in the replacement will become a link.

   Forms:

   1. url
   2. text-a url text-b
   3. linktext (url) text-b

      The parentheses and the url will not be shown, instead the preceeding text
      is linked.

   4. text-a [linktext](url) text-b

      Result is: text-a, no brackets, linktext is linked to url, no parentheses,
      no url, text-b

   All forms can be mixed and added.

   Example output of 'Description':

   \|dt3m_description\|
      |dt3m_description|

   \|dt3m_description_r\|
      |dt3m_description_r|

   \|dt3m_description_json\|
      |dt3m_description_json|

   \|dt3m_description_linked\|
      |dt3m_description_linked|



Postfix example
~~~~~~~~~~~~~~~

Consider this definition:

.. code-block:: ini

   # in Settings.cfg

   [docstypo3-meta]

   description =
      Some explicite description
      follow up line 2
      follow up line 3

   project_home = https://docs.typo3.org/

The replacement text goes to one of three function, depending on what
postfix you use:

=============================  =========== ====  ===============================
Markup                         Function          Replacement
=============================  =========== ====  ===============================
`|dt3m_description|`           str           →   Some explicite description follow up line 2 follow up line 3
`|dt3m_description_r|`         repr          →   '\\nSome explicite description\\nfollow up line 2\\nfollow up line 3'
`|dt3m_description_json|`      json.dumps    →   "\\nSome explicite description\\nfollow up line 2\\nfollow up line 3"
`|dt3m_project_home|`          str           →   https :// docs .typo3 .org/ (think of this as if there were no blanks)
`|dt3m_project_home_link|`     special       →   https://docs.typo3.org/
=============================  =========== ====  ===============================



Demo usage
==========

As field table
--------------

Source
~~~~~~

.. code-block:: rst

   ..

      :Audience:      |dt3m_audience|
      :Author:        |std_author|
      :Contact:       |hto_project_contact|
      :Copyright:     |std_copyright|
      :Description:   |dt3m_description|
      :Discussions:   |hto_project_discussions|
      :Doctype:       |dt3m_doctype|
      :Home:          |hto_project_home|
      :Issues:        |hto_project_issues|
      :Language 1:    |std_language|
      :Language 2:    |dt3m_language|
      :License:       |dt3m_license|
      :Maintainer:    |dt3m_maintainer|
      :Project:       |std_project|
      :Release alias: |std_release|
      :Release:       |release|
      :Rendered:      |today|
      :Repository:    |hto_project_repository|
      :Version alias: |std_version|
      :Version:       |version|
      :Website:       |dt3m_website|

Rendering result
~~~~~~~~~~~~~~~~

   :Audience:      |dt3m_audience|
   :Author:        |std_author|
   :Contact:       |hto_project_contact|
   :Copyright:     |std_copyright|
   :Description:   |dt3m_description|
   :Discussions:   |hto_project_discussions|
   :Doctype:       |dt3m_doctype|
   :Home:          |hto_project_home|
   :Issues:        |hto_project_issues|
   :Language 1:    |std_language|
   :Language 2:    |dt3m_language|
   :License:       |dt3m_license|
   :Maintainer:    |dt3m_maintainer|
   :Project:       |std_project|
   :Release alias: |std_release|
   :Release:       |release|
   :Rendered:      |today|
   :Repository:    |hto_project_repository|
   :Version alias: |std_version|
   :Version:       |version|
   :Website:       |dt3m_website|


Recreate Settings.cfg
---------------------

Source
~~~~~~

.. code-block:: rst

   ..

      .. parsed-literal::

         [general]

         author    =  |std_author|
         copyright =  |std_copyright|
         language  =  |std_language_json|
         project   =  |std_project|
         release   =  |std_release|
         version   =  |std_version|


         [docstypo3-meta]

         audience    =  |dt3m_audience|
         description =  |dt3m_description_json|
         doctype     =  |dt3m_doctype|
         language    =  |dt3m_language|
         license     =  |dt3m_license|
         maintainer  =  |dt3m_maintainer|
         website     =  |dt3m_website|


         [html-theme-options]

         project_contact     =  |hto_project_contact|
         project_discussions =  |hto_project_discussions|
         project_home        =  |hto_project_home|
         project_issues      =  |hto_project_issues|
         project_repository  =  |hto_project_repository|


Rendering result
~~~~~~~~~~~~~~~~

   .. parsed-literal::

      [general]

      author    =  |std_author|
      copyright =  |std_copyright|
      language  =  |std_language_json|
      project   =  |std_project|
      release   =  |std_release|
      version   =  |std_version|


      [docstypo3-meta]

      audience    =  |dt3m_audience|
      description =  |dt3m_description_json|
      doctype     =  |dt3m_doctype|
      language    =  |dt3m_language|
      license     =  |dt3m_license|
      maintainer  =  |dt3m_maintainer|
      website     =  |dt3m_website|


      [html-theme-options]

      project_contact     =  |hto_project_contact|
      project_discussions =  |hto_project_discussions|
      project_home        =  |hto_project_home|
      project_issues      =  |hto_project_issues|
      project_repository  =  |hto_project_repository|



About the settings
==================

1. Missing values will show up as empty string.

2. Choose only one of the example lines if multiple are given here for
   explanatory purposes.


Audience
--------

What audience is this documentation for?

.. confval::     audience

   :Name:        audience
   :Prefix:      dt3m\_
   :Section:     [docstypo3-meta]
   :Notation:    \|dt3m_audience\|
   :Importance:  optional
   :Value:       any text

   Example :file:`Settings.cfg`:

   .. code-block:: ini

      [docstypo3-meta]

      audience = Developers (and everybody interested)

   Example :file:`conf.py`:

   .. code-block:: python

      docstypo3["meta"]["audience"] = "developers (and everybody interested)"

   Example source:

   .. code-block:: rst

      ..

         This book is written for |dt3m_audience|.

   Example result:

      This book is written for developers (and everybody interested).


Author
------

List names and emails of authors.

.. confval::     author

   :Name:        author
   :Prefix:      std\_
   :Section:     [general]
   :Notation:    \|std_author\|
   :Importance:  required
   :Value:       any text
   :Recommended: John Doe <john.doe@example.org>
   :Sphinx docs: `sphinx#confval-author <https://www.sphinx-doc.org/en/master/usage/configuration.html#confval-author>`__

   Example :file:`Settings.cfg`:

   .. code-block:: ini

      [general]

      author = Ernest Hemingway <ernest@example.org>

   Example :file:`conf.py`:

   .. code-block:: python

      author = "Ernest Hemingway <ernest@example.org>"

   Example source:

   .. code-block:: rst

      ..

         The author of those short stories was |std_author|.

   Example result:

      The author of those short stories was Ernest Hemingway
      <ernest@example.org>.


Contact
-------

How to contact the authors. Leave empty or provide just the url or email
address.

.. confval::     project_contact

   :Name:        project_contact
   :Prefix:      hto\_
   :Section:     [html-theme-options]
   :Value:       Leave empty or provide an url like `https://example.org/contact`
                 or `contact@example.org`
   :Notation:    \|hto_project_contact\|
   :Used by:     sphinx_typo3_theme

   Example :file:`Settings.cfg`:

   .. code-block:: ini

      [html-theme-options]

      project_contact = http://example.org/contact

   Example :file:`conf.py`:

   .. code-block:: python

      html_theme_options["project_contact"] = "http://example.org/contact"

   Example source:

   .. code-block:: rst

      ..

         If you want to get in contact visit |hto_project_contact|.

   Example result:

      If you want to get in contact visit http://example.org/contact.


Copyright
---------

((describe))

.. confval::     copyright

   :Name:        copyright
   :Prefix:      std\_
   :Section:     [general]
   :Notation:    \|std_copyright\|
   :Importance:  required
   :Sphinx docs: `sphinx#confval-copyright <https://www.sphinx-doc.org/en/master/usage/configuration.html#confval-copyright>`__
   :Used by:     sphinx_typo3_theme

   Example :file:`Settings.cfg`:

   .. code-block:: ini

      [general]

      copyright = 2021, TYPO3 Documentation Team

   Example :file:`conf.py`:

   .. code-block:: python

      copyright = "2021, TYPO3 Documentation Team"

   Example source:

   .. code-block:: rst

      ..

         This material is copyrighted: |std_copyright|.

   Example result:

      This material is copyrighted: 2021, TYPO3 Documentation Team.


Description
-----------

((describe,

The theme does not used the value in web pages. However, it goes into pdf, epub,
man, ...
))


.. confval::     description

   :Name:        description
   :Prefix:      dt3m\_
   :Section:     [docstypo3-meta]
   :Notation:    \|dt3m_description\|
   :Importance:  optional
   :Value:       Any text. Preferably some sentences suited for the cover of
                 a book.

   Example :file:`Settings.cfg`:

   .. code-block:: ini

      [docstypo3-meta]

      description = This project contains
         examples of documentation code in reStructuredText format. The purpose
         is to demonstrate what the sphinx_typo3_theme can handle and to test
         the theme.


   Example :file:`conf.py`:

   .. code-block:: python

      docstypo3["meta"]["description"] = (
         "This project contains"
         " examples of documentation code in reStructuredText format. The purpose"
         " is to demonstrate what the sphinx_typo3_theme can handle and to test"
         " the theme."
         )

   Example source:

   .. code-block:: rst

      ..

         Abstract:

         |dt3m_description|

   Example result:

      Abstract:

      |dt3m_description|


Discussions
-----------

Leave empty or provide just the url for discussions.

.. confval::     project_discussions

   :Name:        project_discussions
   :Prefix:      hto\_
   :Section:     [html-theme-options]
   :Notation:    \|hto_project_discussions\|
   :Importance:  optional
   :Value:       empty or url
   :Used by:     sphinx_typo3_theme

   Example :file:`Settings.cfg`:

   .. code-block:: ini

      [html-theme-options]

      project_discussions = https://example.org/discussions/

   Example :file:`conf.py`:

   .. code-block:: python

      html_theme_options["project_discussions"] = "https://example.org/discussions/"

   Example source:

   .. code-block:: rst

      ..

         Join the discussions at |hto_project_discussions|.

   Example result:

      Join the discussions at https://example.org/discussions/.


Documentation type
------------------

What kind of documentation is it? ((Tutorial? Reference?, Example? ...))

.. confval::     doctype

   :Name:        doctype
   :Prefix:      dt3m\_
   :Section:     [docstypo3-meta]
   :Notation:    \|dt3m_doctype\|
   :Importance:  recommended
   :Value:       any text

   Example :file:`Settings.cfg`:

   .. code-block:: ini

      [docstypo3-meta]

      doctype = Tutorial

   Example :file:`conf.py`:

   .. code-block:: python

      docstypo3["meta"]["doctype"] = "Tutorial"

   Example source:

   .. code-block:: rst

      ..

         This text is meant as "|dt3m_doctype|".

   Example result:

      This text is meant as "Tutorial".



Home
----

Leave empty or provide an url to the source project.

.. confval::     project_home

   :Name:        project_home
   :Prefix:      hto\_
   :Section:     [html-theme-options]
   :Notation:    \|hto_project_home\|
   :Importance:  optional
   :Value:       empty or url
   :Used by:     sphinx_typo3_theme

   Example :file:`Settings.cfg`:

   .. code-block:: ini

      [html-theme-options]

      project_home = https://example.org/

   Example :file:`conf.py`:

   .. code-block:: python

      html_theme_options["project_home"] = "https://example.org/"

   Example source:

   .. code-block:: rst

      ..

         Visit the project in the internet at |hto_project_home|.

   Example result:

      Visit the project in the internet at https://example.org/.


Issues
------

Leave empty or provide just the url of "Issues".

.. confval::     project_issues

   :Name:        project_issues
   :Prefix:      hto\_
   :Section:     [html-theme-options]
   :Notation:    \|hto_project_issues\|
   :Importance:  optional
   :Value:       empty or url
   :Used by:     sphinx_typo3_theme

   Example :file:`Settings.cfg`:

   .. code-block:: ini

      [html-theme-options]

      project_issues = https://github.com/example/test/issues/

   Example :file:`conf.py`:

   .. code-block:: python

      html_theme_options["project_issues"] = "https://github.com/example/test/issues/"

   Example source:

   .. code-block:: rst

      ..

         Report bugs at |hto_project_issues|.

   Example result:

      Report bugs at https://github.com/example/test/issues/.


Language (std_language)
-----------------------

Default is 'en'. Provide one of the prescribed values that Sphinx recognizes to
specify the language of the documentation.

.. confval::     std_language

   :Name:        language
   :Prefix:      std\_
   :Section:     [general]
   :Notation:    \|std_language\|
   :Default:     empty, which means 'en'
   :Importance:  optional
   :Value:       Must be one of the predefined Sphinx keys like`de` or `en`.
   :Sphinx docs: `sphinx#confval-language <https://www.sphinx-doc.org/en/master/usage/configuration.html#confval-language>`__
   :Used by:     Sphinx translation mechanism

   Sphinx will translate text constants accordingly, like "previous" (en)
   or "zurück" (de).


Language (dt3m_language)
------------------------

To specify the language in a human readable form, that is, as word or sentence.

.. confval::     dt3m_language

   :Name:        language
   :Prefix:      dt3m\_
   :Section:     [docstypo3-meta]
   :Notation:    \|dt3m_language\|
   :Importance:  optional
   :Value:       any text

   Example::

      [docstypo3-meta]
      language = English (US)


License
-------

The name and url of the license *for the documentation.*

.. confval::     license

   :Name:        license
   :Prefix:      dt3m\_
   :Section:     [docstypo3-meta]
   :Notation:    \|dt3m_license\|
   :Importance:  optional
   :Value:       any text
   :Used by:     ((sphinx_typo3_theme, planned for footer))


   Example :file:`Settings.cfg`:

   .. code-block:: ini

      [docstypo3-meta]

      license     = CC-BY 4.0 [https://creativecommons.org/licenses/by/4.0/]

   Example :file:`conf.py`:

   .. code-block:: python

      docstypo3["meta"]["license"] = "CC"

   Example source:

   .. code-block:: rst

      ..

         Respect this license: |dt3m_license|.

         Respect this license: |dt3m_license_linked|.

   Example result:

      Respect this license: CC-BY 4.0 [https://creativecommons.org/licenses/by/4.0/].

      Respect this license: `CC-BY 4.0 <https://creativecommons.org/licenses/by/4.0/>`__.


Maintainer
----------

Who is the maintainer of the documentation?

.. confval::     maintainer

   :Name:        maintainer
   :Prefix:      dt3m\_
   :Section:     [docstypo3-meta]
   :Notation:    \|dt3m_maintainer\|
   :Importance:  optional
   :Value:       any text

   Example :file:`Settings.cfg`:

   .. code-block:: ini

      [docstypo3-meta]

      maintainer = Jon Doe <john.doe@example.org>

   Example :file:`conf.py`:

   .. code-block:: python

      docstypo3["meta"]["maintainer"] = "Jon Doe <john.doe@example.org>"

   Example source:

   .. code-block:: rst

      ..

         In case of doubt ask the maintainer: |dt3m_maintainer|.

   Example result:

      In case of doubt ask the maintainer: Jon Doe <john.doe@example.org>.


Project
-------

Short and concise name of the project that the documentation describes.

.. confval::     project

   :Name:        project
   :Prefix:      std\_
   :Section:     [general]
   :Notation:    \|std_project\|
   :Importance:  required
   :Value:       Any string. Provide the most short and concise name.
   :Sphinx docs: `sphinx#confval-copyright <https://www.sphinx-doc.org/en/master/usage/configuration.html#confval-project>`__
   :Used by:     sphinx_typo3_theme

   Examples::

      [general]

      project = extkey
      project = News
      project = adminpanel
      project = Core changelog


Repository
----------

URL to create a link "Repository" or "Source".

.. confval::     project_repository

   :Name:        project_repository
   :Prefix:      hto\_
   :Section:     [html-theme-options]
   :Notation:    \|hto_project_repository\|
   :Importance:  optional
   :Value:       empty or url
   :Used by:     sphinx_typo3_theme

   Example :file:`Settings.cfg`:

   .. code-block:: ini

      [html-theme-options]

      project_repository = https://github.com/example/test/

   Example :file:`conf.py`:

   .. code-block:: python

      html_theme_options["project_repository"] = "https://github.com/example/test/"

   Example source:

   .. code-block:: rst

      ..

         Get the source from |hto_project_repository|.

   Example result:

      Get the source from https://github.com/example/test/.


Release
-------

((describe))

.. confval::     release

   :Name:        release
   :Prefix:      std\_
   :Section:     [general]
   :Notation:    \|std_release\|
   :Importance:  required
   :Value:       Strict form would be a full semver like `1.2.3`.
                 We are more liberal and usually use the branch name.
   :Remark:      Use the same value for `release` and `version` if there is no
                 difference.
   :Sphinx docs: `sphinx#confval-copyright <https://www.sphinx-doc.org/en/master/usage/configuration.html#confval-release>`__
   :Used by:     sphinx_typo3_theme

   Example:

   .. code-block:: ini

      [general]

      release = 1.2.3
      release = v1.2.dev3
      release = main
      release = develop


Version
-------

((describe))

.. confval::     version

   :Name:        version
   :Prefix:      std\_
   :Section:     [general]
   :Notation:    \|std_version\|
   :Importance:  required
   :Value:       Strict form would be a part of semver like `1.2`.
                 We are more liberal and usually use the branch name.
   :Remark:      Use the same value for `release` and `version` if there is no
                 difference.
   :Sphinx docs: `sphinx#confval-copyright <https://www.sphinx-doc.org/en/master/usage/configuration.html#confval-version>`__
   :Used by:     sphinx_typo3_theme

   Example:

   .. code-block:: ini

      [general]

      version = 1.2
      version = v1.2
      version = main
      version = develop


About "Edit button" settings
============================

For DRC (Docker Rendering Container) >= v3.0.dev21


edit_button_type
----------------

This is a theme variable, that, in the end, controls whether the "Edit on …"
button is to be shown and whether it targets a Bitbucket, GitHub or GitLab like
hosting.

Works together with :confval:`repo_file_edit_url`.

.. confval::     edit_button_type

   :Name:        edit_button_type
   :Prefix:      hto\_
   :Section:     [html_theme_options]
   :Notation:    \|hto_edit_button_type\|
   :Required:    No, since it comes with 'auto' as default value.
   :Default:     auto
   :Value:       Pick one of: auto | bitbucket | github | gitlab | none
   :Used by:     sphinx_typo3_theme

   Example 1:

   .. code-block:: ini

      [html_theme_options]

      # Never show the edit button
      edit_button_type = none

   Example 2:

   .. code-block:: ini

      [html_theme_options]

      # Try to guess the type, looking for
      # '//bitbucket', '//github' or '//gitlab' in the url.
      # This is the default.
      edit_button_type = auto

   Example 3:

   .. code-block:: ini

      [html_theme_options]

      # Do not guess the type. Always show "Edit on GitLab".
      edit_button_type = gitlab


repo_file_edit_url
------------------

This is the real theme variable representing the leading, constant part of the
url that is used to edit files on Bitbucket, GitHub or GitLab.
Usually you don't set this value yourself but specify :confval:`repository_url`
and :confval:`repository_branch`.
However, if you DO specify a value, it will always take effect and no further
calculation takes place.

Works together with :confval:`edit_button_type`.

.. confval::     repo_file_edit_url

   :Name:        repo_file_edit_url
   :Prefix:      hto\_
   :Section:     [html_theme_options]
   :Notation:    \|hto_repo_file_edit_url\|
   :Required:    No, because usually it is calculated internally and derived
                 from :confval:`repository_url` and :confval:`repository_branch`.
   :Default:     "" (empty string)
   :Used by:     sphinx_typo3_theme

   Example 1:

   .. code-block:: ini

      [html_theme_options]

      # Show 'Edit on Bitbucket' button, use custom url
      edit_button_type = bitbucket
      repo_file_edit_url = https://bitbucket.com/USER/REPOSITORY/src/BRANCH

   Example 2:

   .. code-block:: ini

      [html_theme_options]

      # Show 'Edit on GitHub' button, use custom url
      edit_button_type = github
      repo_file_edit_url = https://github.com/USER/REPOSITORY/edit/BRANCH

   Example 3:

   .. code-block:: ini

      [html_theme_options]

      # Show edit on GitLab button, use custom url
      edit_button_type = gitlab
      repo_file_edit_url = https://gitlab.com/USER/REPOSITORY/-/edit/BRANCH


repository_url
--------------

This setting works together with :confval:`repository_branch`. If both are
given the "Edit on …" button will be shown, unless you explicitely turned the
button off by setting :confval:`edit_button_type` to "none".

:confval:`repository_url` and :confval:`repository_branch` are not real theme
variables. They are only used to calculate the real theme variables
:confval:`edit_button_type` and :confval:`repo_file_edit_url` and they are
dropped afterwards. This is why they aren't available for replacements.

.. confval::     repository_url

   :Name:        repository_url
   :Prefix:      \-
   :Section:     [html_theme_options]
   :Notation:    \-
   :Required:    yes, together with :confval:`repository_branch`, if the Edit
                 button is to be shown
   :Default:     "" (empty string)
   :Used for:    internal calculation

   Example 1:

   .. code-block:: ini

      [html_theme_options]

      # Turn on the 'Edit on Bitbucket' button
      repository_url    = https://bitbucket.org/USER/REPOSITORY
      repository_branch = main


   Example 2:

   .. code-block:: ini

      [html_theme_options]

      # Turn on the 'Edit on GitHub' button
      repository_url    = https://github.com/USER/REPOSITORY
      repository_branch = main

   Example 3:

   .. code-block:: ini

      [html_theme_options]

      # Turn on the 'Edit on GitLab' button
      repository_url    = https://gitlab.com/USER/REPOSITORY
      repository_branch = main


repository_branch
-----------------

This setting works together with :confval:`repository_repository`. If both are
given the "Edit on …" button will be shown, unless you explicitely turned the
button off by setting :confval:`edit_button_type` to "none".

.. confval::     repository_branch

   :Name:        repository_branch
   :Prefix:      \-
   :Section:     [html_theme_options]
   :Notation:    \-
   :Required:    yes, together with :confval:`repository_url`, if the Edit
                 button is to be shown
   :Default:     "" (empty string)
   :Used for:    internal calculation


github_branch (deprecated)
--------------------------

Is used as value for :confval:`repository_branch`, if that value is otherwise
empty.

.. confval::     github_branch (deprecated)

   :Name:        github_branch
   :Prefix:      \-
   :Section:     [html_theme_options]
   :Notation:    \-
   :Required:    no, deprecated, use :confval:`repository_branch` instead
   :Default:     "" (empty string)
   :Used for:    internal calculation


github_repository (deprecated)
------------------------------

Is used as value `https://github.com/[github_repository]` for
:confval:`repository_url`, if that value is otherwise empty.

.. confval::     github_repository (deprecated)

   :Name:        github_repository
   :Prefix:      \-
   :Section:     [html_theme_options]
   :Notation:    \-
   :Required:    no, deprecated, use :confval:`repository_url` instead
   :Default:     "" (empty string)
   :Value:       user/repository
   :Used for:    internal calculation

   Example:

   .. code-block:: ini

      [html_theme_options]

      # Deprecated form - remove these two lines
      github_repository  = USER/REPOSITORY
      github_branch      = main

      # Correct form - use these two lines instead
      repository_url    = https://gitlab.com/USER/REPOSITORY
      repository_branch = main




More pages
==========

.. toctree::
   :glob:

   */Index
   *