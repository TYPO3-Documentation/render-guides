..  include:: /Includes.rst.txt

..  _CodeFolding:

============
Code folding
============

A code block can fold the lines that are not the point of the example. Folded
lines are hidden behind a placeholder that opens them; the button beside the
copy button unfolds the whole block. Copying always takes every line, and
without JavaScript, in print and in the Markdown output everything is shown.

..  contents:: This page
    :local:

The header of a PHP file is folded by default
=============================================

Without any option, the open tag, :php:`declare`, :php:`namespace` and
:php:`use` statements of a PHP example are folded, together with the comments
and blank lines between them. The docblock above the class stays visible.

..  literalinclude:: _codesnippets/_MyEventListener.php
    :caption: EXT:my_extension/Classes/EventListener/MyEventListener.php

The same works for a :rst:`code-block` written inline:

..  code-block:: php
    :caption: EXT:my_extension/Classes/Service/GreetingService.php

    <?php

    declare(strict_types=1);

    namespace MyVendor\MyExtension\Service;

    use TYPO3\CMS\Core\Localization\LanguageServiceFactory;

    final readonly class GreetingService
    {
        public function __construct(
            private LanguageServiceFactory $languageServiceFactory,
        ) {}
    }

Showing only the lines that matter
==================================

With :rst:`:visible-lines:` the author names the lines to show. Everything else
is folded, here the header, the constructor and the second action:

..  code-block:: rst

    ..  literalinclude:: _codesnippets/_MyController.php
        :caption: EXT:my_extension/Classes/Controller/BlogController.php
        :visible-lines: 15-16, 24-35, 51-58

..  literalinclude:: _codesnippets/_MyController.php
    :caption: EXT:my_extension/Classes/Controller/BlogController.php
    :visible-lines: 15-16, 24-35, 51-58

The option works on a :rst:`code-block` in any language:

..  code-block:: yaml
    :caption: EXT:my_extension/Configuration/Services.yaml
    :visible-lines: 7-9

    services:
      _defaults:
        autowire: true
        autoconfigure: true
        public: false

      MyVendor\MyExtension\:
        resource: '../Classes/*'
        exclude: '../Classes/Domain/Model/*'

Other languages
===============

Only PHP folds on its own. In any other language, :rst:`:visible-lines:`
folds what it leaves out, and a comment, a CDATA section or a string that
spans the fold keeps its highlighting when the block is opened.

XML, with a comment over three lines and a CDATA section folded away:

..  code-block:: xml
    :caption: EXT:my_extension/Configuration/FlexForms/Settings.xml
    :visible-lines: 5-6

    <?xml version="1.0" encoding="UTF-8"?>
    <!--
        The sheets of the plugin settings,
        one per tab in the backend form -->
    <T3DataStructure>
        <sheets>
            <![CDATA[
            Settings of the blog plugin
            ]]>
        </sheets>
    </T3DataStructure>

TypoScript, with a multi-line comment inside a fold:

..  code-block:: typoscript
    :caption: EXT:my_sitepackage/Configuration/Sets/MySet/setup.typoscript
    :visible-lines: 3-5

    # Default PAGE object
    page = PAGE
    page.10 = FLUIDTEMPLATE
    page.10 {
        templateName = Default
        /* The content of the main column,
           rendered by fluid_styled_content */
        variables {
            content < styles.content.get
        }
    }

A Fluid template, showing only the section:

..  code-block:: html
    :caption: EXT:my_sitepackage/Resources/Private/Templates/Page/Default.html
    :visible-lines: 4

    <html xmlns:f="http://typo3.org/ns/TYPO3/CMS/Fluid/ViewHelpers"
          data-namespace-typo3-fluid="true">
    <f:layout name="Default" />
    <f:section name="Main"><h1>{data.title}</h1></f:section>
    </html>

JavaScript, folding the middle of a template literal:

..  code-block:: javascript
    :caption: EXT:my_extension/Resources/Public/JavaScript/greeting.js
    :visible-lines: 1, 5

    const greeting = 'Hello';
    const text = `line one
    line two
    line three`;
    console.log(greeting, text);

A code block without a language; its characters are text, not markup:

..  code-block::
    :visible-lines: 2

    <b>not markup</b> & "quotes"
    the only visible line
    x < y > z

A diff with line numbers:

..  code-block:: diff
    :caption: Patch for EXT:my_extension/Classes/Service/MyService.php
    :linenos:
    :visible-lines: 3-4

    --- a/Classes/Service/MyService.php
    +++ b/Classes/Service/MyService.php
    -        return $this->legacyCall();
    +        return $this->call();
             // unchanged context

Emphasized lines are never folded
=================================

The header fold ends before an emphasized line, so the :php:`use` statement
this example is about stays in view:

..  literalinclude:: _codesnippets/_MyMiddleware.php
    :caption: EXT:my_extension/Classes/Middleware/MyMiddleware.php
    :emphasize-lines: 10

With line numbers
=================

Line numbers keep counting across a fold:

..  literalinclude:: _codesnippets/_MyEventListener.php
    :caption: EXT:my_extension/Classes/EventListener/MyEventListener.php
    :linenos:
    :emphasize-lines: 30-32

Nothing to fold
===============

A header of fewer than three lines is not worth a click and stays as it is:

..  literalinclude:: _codesnippets/_tt_content.php
    :caption: EXT:my_extension/Configuration/TCA/Overrides/tt_content.php

A file whose header takes most of it still folds it, as long as code follows:

..  literalinclude:: _codesnippets/_ext_localconf.php
    :caption: EXT:my_extension/ext_localconf.php

Folding switched off
====================

:rst:`:visible-lines: all` shows every line:

..  literalinclude:: _codesnippets/_MyEventListener.php
    :caption: EXT:my_extension/Classes/EventListener/MyEventListener.php
    :visible-lines: all
