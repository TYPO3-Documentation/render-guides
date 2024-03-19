.. include:: /Includes.rst.txt

.. _Codeblocks:

==========
Codeblocks
==========

..  contents:: This page
    :local:


Basic examples
==============

..  code-block:: shell

    ls -al

Code-block with line numbers
============================

..  code-block:: rst
    :caption: Example of 'contents' directive
    :linenos:
    :emphasize-lines: 2,3
    :force:

    This is an example block. Next two line have 'emphasis' background color.
    With another line.
    And a third one.

    ..  code-block:: rst
        :caption: Example of 'contents' directive
        :linenos:
        :emphasize-lines: 2,3
        :force:

        This is an example block.
        With another line.
        And a third one.



PHP
===

..  code-block:: php
    :caption:  CustomCategoryProcessor.php

    <?php

    declare(strict_types=1);

    /*
     * This file is part of the TYPO3 CMS project. [...]
     */

    namespace T3docs\Examples\DataProcessing;

    use T3docs\Examples\Domain\Repository\CategoryRepository;
    use TYPO3\CMS\Core\Utility\GeneralUtility;
    use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;
    use TYPO3\CMS\Frontend\ContentObject\DataProcessorInterface;

    /**
     * Class for data processing comma separated categories
     */
    final class CustomCategoryProcessor implements DataProcessorInterface
    {
        /**
         * Process data for the content element "My new content element"
         *
         * @param ContentObjectRenderer $cObj The data of the content element or page
         * @param array $contentObjectConfiguration The configuration of Content Object
         * @param array $processorConfiguration The configuration of this processor
         * @param array $processedData Key/value store of processed data (e.g. to be passed to a Fluid View)
         * @return array the processed data as key/value store
         */
        public function process(
            ContentObjectRenderer $cObj,
            array $contentObjectConfiguration,
            array $processorConfiguration,
            array $processedData
        ) {
            if (isset($processorConfiguration['if.']) && !$cObj->checkIf($processorConfiguration['if.'])) {
                return $processedData;
            }
            // categories by comma separated list
            $categoryIdList = $cObj->stdWrapValue('categoryList', $processorConfiguration ?? []);
            $categories = [];
            if ($categoryIdList) {
                $categoryIdList = GeneralUtility::intExplode(',', (string)$categoryIdList, true);
                /** @var CategoryRepository $categoryRepository */
                $categoryRepository = GeneralUtility::makeInstance(CategoryRepository::class);
                foreach ($categoryIdList as $categoryId) {
                    $categories[] = $categoryRepository->findByUid($categoryId);
                }
                // set the categories into a variable, default "categories"
                $targetVariableName = $cObj->stdWrapValue('as', $processorConfiguration, 'categories');
                $processedData[$targetVariableName] = $categories;
            }
            return $processedData;
        }
    }

JavaScript
==========

..  code-block:: javascript

    var makeNoise = function() {
      console.log("Pling!");
    };

    makeNoise();
    // → Pling!

    var power = function(base, exponent) {
      var result = 1;
      for (var count = 0; count < exponent; count++)
        result *= base;
      return result;
    };

    console.log(power(2, 10));
    // → 1024


JSON
====

..  code-block:: json

    [
      {
        "title": "apples",
        "count": [12000, 20000],
        "description": {"text": "...", "sensitive": false}
      },
      {
        "title": "oranges",
        "count": [17500, null],
        "description": {"text": "...", "sensitive": false}
      }
    ]


Makefile
========

..  code-block:: makefile

    # Makefile

    BUILDDIR      = _build
    EXTRAS       ?= $(BUILDDIR)/extras

    .PHONY: main clean

    main:
       @echo "Building main facility..."
       build_main $(BUILDDIR)

    clean:
       rm -rf $(BUILDDIR)/*


Markdown
========

..  code-block:: markdown

    # hello world

    you can write text [with links](https://example.org) inline or [link references][1].

    * one _thing_ has *em*phasis
    * two __things__ are **bold**

    [1]: https://example.org

SQL
===

..  code-block:: sql

    BEGIN;
    CREATE TABLE "topic" (
        -- This is the greatest table of all time
        "id" serial NOT NULL PRIMARY KEY,
        "forum_id" integer NOT NULL,
        "subject" varchar(255) NOT NULL -- Because nobody likes an empty subject
    );
    ALTER TABLE "topic" ADD CONSTRAINT forum_id FOREIGN KEY ("forum_id") REFERENCES "forum" ("id");

    -- Initials
    insert into "topic" ("forum_id", "subject") values (2, 'D''artagnian');

    select /* comment */ count(*) from cicero_forum;

    -- this line lacks ; at the end to allow people to be sloppy and omit it in one-liners
    /*
    but who cares?
    */
    COMMIT



HTML
====

..  code-block:: html

    <!DOCTYPE html>
    <title>Title</title>

    <style>body {width: 500px;}</style>

    <script type="application/javascript">
      function $init() {return true;}
    </script>

    <body>
      <p checked class="title" id='title'>Title</p>
      <!-- here goes the rest of the page -->
    </body>


Xml
===

..  code-block:: xml

    <?xml version="1.0"?>
    <response value="ok" xml:lang="en">
      <text>Ok</text>
      <comment html_allowed="true"/>
      <ns1:description><![CDATA[
      CDATA is <not> magical.
      ]]></ns1:description>
      <a></a> <a/>
    </response>
