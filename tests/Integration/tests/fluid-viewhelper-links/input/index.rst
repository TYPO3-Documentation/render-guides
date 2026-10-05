=================
Fluid ViewHelpers
=================

A ViewHelper the reference documents, as a tag:
:fluid:`<f:format.html>{record.bodytext}</f:format.html>`, as a closing tag:
:fluid:`</f:format.html>`, as an inline call:
:fluid:`{f:translate(key: 'title')}` or :fluid:`{f:uri.image}`, and by its
name alone:
:fluid:`be:moduleLink`.

A ViewHelper the reference does not document: :fluid:`<my:custom.thing>`.

Fluid that names no ViewHelper: :fluid:`{page.uid}`.
