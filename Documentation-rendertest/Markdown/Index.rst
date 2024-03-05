
==============
Markdown files
==============

Here we add source files of the same folder to verify that the processing of markdown files is working.

The DRC (TYPO3 Docker Rendering Container) can be configured to process markdown files directly. As a drawback
this turns off the Sphinx caching abilities. So most probably the DRC will be configured to process \*.rst files only
as this keeps the caching abilities function. In this case, the `pandoc
<https://pandoc.org/>`__ utility is used to convert each markdown file to reST in a first step.

There should - at least - be a subpage *"Keep a changelog" example*.

.. rst-class:: compact-list
.. toctree::
   :glob:

   *

