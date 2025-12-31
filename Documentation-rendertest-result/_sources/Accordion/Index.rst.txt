..  include:: /Includes.rst.txt
..  index:: ! Accordion
..  _accordion:

=========
Accordion
=========

..  accordion::
    :name: accordionExample

    ..  accordion-item:: Accordion Item #1
        :name: headingOne
        :header-level: 2
        :show:

        **This is the first item's accordion body.** It is shown by default, until the collapse plugin adds the
        appropriate classes that we use to style each element. These classes control the overall appearance,
        as well as the showing and hiding via CSS transitions.

        You can modify any of this with custom CSS
        or overriding our default variables. It's also worth noting that just about any HTML can go within
        the `.accordion-body`, though the transition does limit overflow.

    ..  accordion-item:: Accordion Item #2
        :name: headingTwo
        :header-level: 2

        Placeholder content for this accordion, which is intended to demonstrate the .accordion-flush class.
        This is the second item's accordion body. Let's imagine this being filled with some actual content.

    ..  accordion-item:: Accordion Item #3
        :name: headingThree
        :header-level: 2

        Placeholder content for this accordion, which is intended to demonstrate the .accordion-flush class.
        This is the third item's accordion body. Nothing more exciting happening here in terms of content, but
        just filling up the space to make it look,
        at least at first glance, a bit more representative of how this would look in a real-world application.

Accordion all closed
====================

..  accordion::
    :name: accordionExample2

    ..  accordion-item:: Accordion Item #1
        :name: headingOne2
        :header-level: 3

        Placeholder content for this accordion

    ..  accordion-item:: Accordion Item #2
        :name: headingTwo2
        :header-level: 3

        Placeholder content for this accordion, which is intended to demonstrate the .accordion-flush class.
        This is the second item's accordion body. Let's imagine this being filled with some actual content.

    ..  accordion-item:: Accordion Item #3
        :name: headingThree2
        :header-level: 3

         Let's imagine this being filled with some actual content.


Accordion with complex content
==============================

..  accordion::
    :name: accordionExample3

    ..  accordion-item:: Accordion Item #1
        :name: headingOne3
        :header-level: 3

        .. tabs::

           .. tab:: Apples

              Apples are green, or sometimes red.

           .. tab:: Pears

              Pears are green.

           .. tab:: Oranges

              Oranges are orange.

    ..  accordion-item:: Accordion Item #2
        :name: headingTwo3
        :header-level: 3
        :show:

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


    ..  accordion-item:: Accordion Item #3
        :name: headingThree3
        :header-level: 3

        .. image:: ../images/q150_ffffff.png
           :alt: Image with background color #ffffff
           :class: with-border with-shadow
