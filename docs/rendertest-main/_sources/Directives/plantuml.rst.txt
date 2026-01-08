.. include:: /Includes.rst.txt
.. index:: plantuml; basic examples
.. _Plantuml-basic-examples:

=======================
Plantuml basic examples
=======================

Using inline notation
=====================

Source:

.. code-block:: rst

   .. uml::
      :caption: Inline diagram

      Bob -> Alice : hello
      Alice -> Bob : ok

Rendered:

.. uml::
   :caption: Inline diagram

   Bob -> Alice : hello
   Alice -> Bob : ok


