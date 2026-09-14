==========
PHP domain
==========

..  php:namespace:: LibraryName

..  php:class:: Renderer

    Turns a document into something else.

    ..  php:const:: FORMAT = 'md'

        The format this renderer writes.

    ..  php:attr:: target

        Where the output goes.

    ..  php:method:: render(Document $document, string $format = 'md')

        Renders one document.

        :param Document $document: The document to render.
        :returns: The rendered document.

    ..  php:staticmethod:: create()

        Builds a renderer.

..  php:interface:: RendererInterface

    What every renderer can do.

..  php:trait:: RendersMarkdown

    Shared by the Markdown renderers.

..  php:exception:: RenderFailed

    Thrown when a document cannot be rendered.
