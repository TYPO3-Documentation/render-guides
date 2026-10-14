..  include:: /Includes.rst.txt

..  _api-class:

==========================
Classes from the TYPO3 API
==========================

api.typo3.org writes every class, interface, trait, and enum of the TYPO3
Core as a JSON file, with its members. A manual shows such a type with the
:rst:`..  typo3:api-class::` directive. The directive reads the JSON file
when the manual is rendered, so the manual contains no generated
reStructuredText.

..  _api-class-json:

One JSON file per type
======================

The template of api.typo3.org in :path:`packages/typo3-api` writes a JSON file
beside the HTML page of each type. The path of the file is the fully
qualified name of the type, with a slash for each backslash:

..  code-block:: text

    https://api.typo3.org/main/classes/TYPO3/CMS/Impexp/Event/BeforeImportEvent.json

The phpDocumentor extension in
:path:`packages/typo3-api/extensions/Typo3Json` builds the content of the
file. Shortened, it looks like this:

..  code-block:: json

    {
      "schema": 1,
      "type": "class",
      "fqn": "TYPO3\\CMS\\Impexp\\Event\\BeforeImportEvent",
      "namespace": "TYPO3\\CMS\\Impexp\\Event",
      "name": "BeforeImportEvent",
      "file": "typo3/sysext/impexp/Classes/Event/BeforeImportEvent.php",
      "line": 25,
      "modifiers": ["final", "readonly"],
      "summary": "This event is triggered when an import file is about to …",
      "description": "",
      "deprecated": null,
      "internal": null,
      "extends": null,
      "implements": [],
      "uses": [],
      "constants": [],
      "properties": [],
      "methods": [
        {
          "name": "getFile",
          "visibility": "public",
          "modifiers": [],
          "parameters": [],
          "returns": {"type": "string", "description": ""},
          "summary": "",
          "description": "",
          "deprecated": null,
          "internal": null,
          "declaredIn": "TYPO3\\CMS\\Impexp\\Event\\BeforeImportEvent"
        }
      ],
      "dispatchedBy": [
        "TYPO3\\CMS\\Impexp\\Utility\\ImportExportUtility::importT3DFile()"
      ]
    }

The file has the following properties:

*   `schema` is the version of the format. It changes only when a reader
    has to know about the change. The theme reads version 1 and warns about
    any other version.
*   The members contain the inherited ones. `declaredIn` names the type
    that declares a member, so a reader can tell them apart.
*   `deprecated` and `internal` are `null` when the tag is not there.
    Otherwise they are the text of the tag, which can be empty.
*   Descriptions are the text of the doc comment. An inline tag, such as
    `{@see \\Foo\\Bar}`, stays as it is in the source. A link to
    api.typo3.org would not work in a manual.
*   An enum also has `backedType` and `cases`.
*   A method that dispatches a PSR-14 event lists the event in
    `dispatches`. An event lists the methods that dispatch it in
    `dispatchedBy`. The extension in
    :path:`packages/typo3-api/extensions/Typo3Events` provides both.

..  _api-class-directive:

Show a type in a manual
=======================

Write the fully qualified name of the type after the directive:

..  code-block:: rst

    ..  typo3:api-class:: \TYPO3\CMS\Impexp\Event\BeforeImportEvent

The directive loads the file for the TYPO3 version that
:rst:`typo3-core-preferred` in :file:`guides.xml` names. It then writes the
type as :rst:`php:class`, :rst:`php:method`, and the other directives of the
PHP domain, and parses them like an included file. The anchors and the
entries in :file:`objects.inv.json` are thus the same as those of a snippet
that :composer:`t3docs/codesnippet` generated. A page can replace the
:rst:`..  include::` of such a snippet with the directive, and links to the
type and its members keep working.

The directive leaves out a type that is marked `@internal`, and warns about
it. It also warns about a type that the API does not document. A class of a
third party package, such as
:php:`\Psr\Http\Server\MiddlewareInterface`, is not part of the API.

..  _api-class-directive-options:

Options
-------

The options are the settings of the `createPhpClassDocs` action of
:composer:`t3docs/codesnippet`. Without an option, the directive shows what
a snippet shows by default.

`:members:`
    Shows only these constants, properties, methods, and cases, in this
    order. Separate the names with commas. A method name is not case
    sensitive, as in PHP. The directive warns about a name that the type
    does not have.

`:visibility:`
    The visibilities to show, separated by commas, by default `public`.
    For example, `public, protected` also shows the protected members.

`:include-internal:`
    Also shows a type or a member that is marked `@internal`.

`:exclude-deprecated:`
    Leaves out a deprecated method. By default, a deprecated method is shown
    with a notice.

`:include-constructor:`
    Also shows `__construct()`.

`:no-class-comment:`
    Leaves out the description of the type.

`:no-member-comment:`
    Leaves out the descriptions of the members.

`:no-parameters:`
    Leaves out the descriptions of the parameters and of the return value.

`:noindex:`
    Does not register the type for links and the index. Use it when a page
    shows a type a second time.

`:noindex-members:`
    Does not register the members.

..  _api-class-directive-differences:

Differences from a generated snippet
------------------------------------

The output is not identical to that of a generated snippet. The directive
shows the following details that a snippet leaves out or gets wrong:

*   A class is marked `final` and `readonly`.
*   A type from the doc comment is used where it is more precise than the
    one in the signature, for example `string[]` instead of `array`.
*   A description of a parameter with a type such as `array<string, mixed>`
    is shown. The snippet showed "the values" instead.
*   A backslash in a description is kept.
*   The default of a parameter is shown as it is written in the source, for
    example
    :php-short:`\TYPO3\CMS\Backend\Template\Components\ButtonBar::BUTTON_POSITION_LEFT`
    instead of `'left'`.
