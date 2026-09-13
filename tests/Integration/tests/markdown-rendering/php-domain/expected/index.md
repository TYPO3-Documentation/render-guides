# PHP domain

-   **class Renderer**

    -   *Fully qualified name:* `\LibraryName\Renderer`

    Turns a document into something else.

    -   **const FORMAT = 'md'**

        The format this renderer writes.

    -   **target**

        Where the output goes.

    -   **render(Document $document, string $format = 'md')**

        Renders one document.

        -   *param Document $document:* The document to render.

        *Returns:* The rendered document.

    -   **static create()**

        Builds a renderer.

-   **interface RendererInterface**

    -   *Fully qualified name:* `\LibraryName\RendererInterface`

    What every renderer can do.

-   **trait RendersMarkdown**

    -   *Fully qualified name:* `\LibraryName\RendersMarkdown`

    Shared by the Markdown renderers.

-   **exception RenderFailed**

    -   *Fully qualified name:* `\LibraryName\RenderFailed`

    Thrown when a document cannot be rendered.
