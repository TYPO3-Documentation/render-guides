.. include:: /Includes.rst.txt

.. _sphinxcontrib-PHP-Domain:

========================
Phpdomain
========================

.. seealso::

   *  Find the original Sphinx extension at PyPi, the Python Package Index:
      `sphinxcontrib-phpdomain
      <https://pypi.org/project/sphinxcontrib-phpdomain/>`__.

   *  We are using a fork and the branch `develop-for-typo3
      <https://github.com/TYPO3-Documentation/sphinxcontrib-phpdomain/tree/develop-for-typo3>`__


.. contents:: This page
   :backlinks: top
   :class: compact-list
   :depth: 99
   :local:


Quick Sample
------------

This is source:

.. code-block:: rst

   .. php:class:: DateTime

      Datetime class

      .. php:method:: setDate($year, $month, $day)

         Set the date.

         :param int $year: The year.
         :param int $month: The month.
         :param int $day: The day.
         :returns: Either false on failure, or the datetime object for method chaining.


      .. php:method:: setTime($hour, $minute[, $second])

         Set the time.

         :param int $hour: The hour
         :param int $minute: The minute
         :param int $second: The second
         :returns: Either false on failure, or the datetime object for method chaining.

      .. php:const:: ATOM

         Y-m-d\TH:i:sP


.. php:class:: DateTime

   Datetime class

   .. php:method:: setDate($year, $month, $day)

      Set the date.

      :param int $year: The year.
      :param int $month: The month.
      :param int $day: The day.
      :returns: Either false on failure, or the datetime object for method chaining.


   .. php:method:: setTime($hour, $minute[, $second])

      Set the time.

      :param int $hour: The hour
      :param int $minute: The minute
      :param int $second: The second
      :returns: Either false on failure, or the datetime object for method chaining.

   .. php:const:: ATOM

      Y-m-d\TH:i:sP



Acceptance tests for PHPdomain
------------------------------

Credit: The source for this section was taken from the original `GitHub
repository markstory/sphinxcontrib-phpdomain
<https://github.com/markstory/sphinxcontrib-phpdomain>`__.


Classes
=======

..  php:class:: DateTime

    Datetime class

    ..  php:method:: setDate($year, $month, $day)

        Set the date in the datetime object

        :param int $year: The year.
        :param int $month: The month.
        :param int $day: The day.

    ..  php:method:: setTime($hour, $minute[, $second])

        Set the time

        :param int $hour: The hour
        :param int $minute: The minute
        :param int $second: The second

    ..  php:method:: getLastErrors()
        :public:
        :static:

        Returns the warnings and errors

        :returns: array Returns array containing info about warnings and errors.

    ..  php:const:: ATOM

        `Y-m-d\TH:i:sP`

    ..  php:attr:: testattr

        Value of some attribute

..  php:class:: OtherClass

    Another class

    ..  php:method:: update($arg = '', $arg2 = [], $arg3 = [])

        Update something.

    ..  php:attr:: nonIndentedAttribute

        This attribute wasn't indented

    ..  php:const:: NO_INDENT

        This class constant wasn't indented

    ..  php:staticmethod:: staticMethod()

        A static method.


Exceptions
==========

.. php:exception:: InvalidArgumentException

   Throw when you get an argument that is bad.


Interfaces
==========

.. php:interface:: DateTimeInterface

   Datetime interface

   .. php:method:: setDate($year, $month, $day)

      Set the date in the datetime object

      :param int $year: The year.
      :param int $month: The month.
      :param int $day: The day.

   .. php:method:: setTime($hour, $minute[, $second])

      Set the time

      :param int $hour: The hour
      :param int $minute: The minute
      :param int $second: The second

   .. php:const:: ATOM

      Y-m-d\TH:i:sP

   .. php:attr:: testattr

      Value of some attribute

.. php:interface:: OtherInterface

   Another interface


Traits
======

.. php:trait:: LogTrait

   A logging trait

   .. php:method:: log($level, $string)

      A method description.


Test Case - Global symbols with no namespaces
---------------------------------------------

:php:class:`DateTime`

:php:func:`DateTime::setTime()`

:php:func:`DateTime::getLastErrors()`

:php:func:`~DateTime::setDate()`

:php:const:`DateTime::ATOM`

:php:attr:`DateTime::$testattr`

:php:func:`OtherClass::update`

:php:attr:`OtherClass::$nonIndentedAttribute`

:php:const:`OtherClass::NO_INDENT`

:php:func:`OtherClass::staticMethod`

:php:exc:`InvalidArgumentException`

:php:interface:`DateTimeInterface`

:php:func:`DateTimeInterface::setTime()`

:php:func:`~DateTimeInterface::setDate()`

:php:const:`DateTimeInterface::ATOM`

:php:attr:`DateTimeInterface::$testattr`

:php:interface:`OtherInterface`

:php:trait:`LogTrait`

:php:func:`LogTrait::log()`

.. php:namespace:: LibraryName


Namespaced elements
===================

.. php:exception:: NamespaceException

   This exception is in a namespace.


..  php:class:: LibraryClass

    A class in a namespace

    ..  php:method:: instanceMethod($foo)

        An instance method

    ..  php:const:: TEST_CONST

        Test constant

    ..  php:attr:: property

        A property!

    ..  php:staticmethod:: staticMethod()

        A static method in a namespace

..  php:class:: NamespaceClass

    A class in the namespace, no indenting on children

    ..  php:method:: firstMethod($one, $two)

        A normal instance method.

    ..  php:attr:: property

        A property

    ..  php:const:: NAMESPACE_CONST

        Const on class in namespace

    ..  php:staticmethod:: namespaceStatic($foo)

        A static method here.

..  php:class:: LibraryClassFinal
    :final:

    A final class

    ..  php:method:: firstMethod($one, $two)
        :public:

        A public instance method.

    ..  php:method:: secondMethod($one, $two)
        :protected:

        A protected instance method.

    ..  php:method:: thirdMethod($one, $two)
        :private:

        A private instance method.

    ..  php:method:: fourthMethod($one, $two)
        :static:

        A static method.

    ..  php:method:: fifthMethod($one, $two)
        :protected:
        :final:

        A protected final method.

..  php:class:: LibraryClassAbstract
    :abstract:

    An abstract class

..  php:interface:: LibraryInterface

    A interface in a namespace

    ..  php:method:: instanceMethod($foo)

        An instance method

..  php:trait:: TemplateTrait

    A trait in a namespace

    ..  php:method:: render($template)

        Render a template.


Test Case - not including namespace
-----------------------------------

:php:ns:`LibraryName`

:php:class:`LibraryName\LibraryClass`

:php:class:`\LibraryName\\LibraryClass`

:php:func:`LibraryName\LibraryClass::instanceMethod`

:php:func:`LibraryName\LibraryClass::staticMethod()`

:php:attr:`LibraryName\LibraryClass::$property`

:php:const:`LibraryName\LibraryClass::TEST_CONST`

:php:class:`\LibraryName\NamespaceClass`

:php:func:`\LibraryName\NamespaceClass::firstMethod`

:php:attr:`\LibraryName\NamespaceClass::$property`

:php:const:`\LibraryName\NamespaceClass::NAMESPACE_CONST`

:php:class:`\LibraryName\LibraryClassFinal`

:php:meth:`\LibraryName\LibraryClassFinal::firstMethod`

:php:meth:`\LibraryName\LibraryClassFinal::secondMethod`

:php:meth:`\LibraryName\LibraryClassFinal::thirdMethod`

:php:meth:`\LibraryName\LibraryClassFinal::fourthMethod`

:php:meth:`\LibraryName\LibraryClassFinal::fifthMethod`

:php:interface:`\\LibraryName\\LibraryInterface`

:php:func:`\LibraryName\LibraryInterface::instanceMethod`

:php:exc:`\LibraryName\NamespaceException`

:php:trait:`LibraryName\\TemplateTrait`

:php:func:`LibraryName\\TemplateTrait::render()`

Test Case - global access
-------------------------

:php:class:`DateTime`

:php:func:`DateTime::setTime()`

:php:global:`$global_var`

:php:attr:`LibraryName\\LibraryClass::$property`

:php:const:`LibraryName\\LibraryClass::TEST_CONST`

:php:interface:`DateTimeInterface`

:php:func:`DateTimeInterface::setTime()`


Any Cross Ref
=============

:any:`LibraryName\\SubPackage\\NestedNamespaceException`

:any:`DateTimeInterface::$testattr`



Nested namespaces
=================

.. php:namespace:: LibraryName\SubPackage

.. php:exception:: NestedNamespaceException

   In a package

.. php:class:: SubpackageClass

   A class in a subpackage

.. php:interface:: SubpackageInterface

   A class in a subpackage

Test Case - Test subpackage links
---------------------------------

:php:ns:`LibraryName\\SubPackage`

:php:class:`\\LibraryName\\SubPackage\\SubpackageClass`

:php:interface:`\\LibraryName\\SubPackage\\SubpackageInterface`

:php:exc:`\\LibraryName\\SubPackage\\NestedNamespaceException`


Return Types
============

.. php:namespace:: OtherLibrary

.. php:class:: ReturningClass

   A class to do some returning.

   .. php:method:: returnClassFromSameNamespace()

      :returns: An object instance of a class from the same namespace.
      :returntype: OtherLibrary\\ReturnedClass

   .. php:method:: returnClassFromOtherNamespace()

      :returns: An object instance of a class from another namespace.
      :returntype: LibraryName\\SubPackage\\SubpackageInterface

   .. php:method:: returnClassConstant()

      :returns: The value of a specific class constant.
      :returntype: LibraryName\\NamespaceClass::NAMESPACE_CONST

   .. php:method:: returnGlobalConstant()

      :returns: The value of a specific global constant.
      :returntype: SOME_CONSTANT

   .. php:method:: returnExceptionInstance()

      :returns: An instance of an exception.
      :returntype: InvalidArgumentException

   .. php:method:: returnScalarType()

      :returns: A scalar string type.
      :returntype: string

   .. php:method:: returnUnionType()

      :returns: Any of a whole bunch of things specified with a PHP 8 union type.
      :returntype: `int|string|OtherLibrary\\ReturnedClass|LibraryName\\SubPackage\\SubpackageInterface|null`

.. php:class:: ReturnedClass

   A class to return.



Top Level Namespace
-------------------

Credit: The source for this section was taken from the original `GitHub
repository markstory/sphinxcontrib-phpdomain
<https://github.com/markstory/sphinxcontrib-phpdomain>`__.


namespace ``Imagine\Draw``

.. php:namespace:: Imagine\Draw

.. php:class:: DrawerInterface

Instance of this interface is returned by.

.. php:method:: arc(PointInterface $center, BoxInterface $size, $start, $end, Color $color)

   Draws an arc on a starting at a given x, y coordinates under a given start and end angles

   :param Imagine\Image\PointInterface $center: Center of the arc.
   :param Imagine\Image\BoxInterface $size: Size of the bounding box.
   :param integer $start: Start angle.
   :param integer $end: End angle.
   :param Imagine\Image\Color $color: Line color.

   :throws: Imagine\Exception\RuntimeException

   :returns: Imagine\Draw\DrawerInterface
