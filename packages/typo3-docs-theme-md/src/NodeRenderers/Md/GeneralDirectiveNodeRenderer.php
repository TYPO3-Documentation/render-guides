<?php

declare(strict_types=1);

namespace T3Docs\Typo3DocsThemeMd\NodeRenderers\Md;

use InvalidArgumentException;
use phpDocumentor\Guides\NodeRenderers\NodeRenderer;
use phpDocumentor\Guides\Nodes\Node;
use phpDocumentor\Guides\RenderContext;
use phpDocumentor\Guides\RestructuredText\Nodes\GeneralDirectiveNode;
use phpDocumentor\Guides\TemplateRenderer;

use function is_a;
use function preg_replace;
use function str_replace;

/**
 * Looks up a Markdown template per directive name, the way the HTML pipeline
 * does: "rubric" is rendered by "body/directive/rubric.md.twig".
 *
 * Directives reach Markdown as GeneralDirectiveNode, so without this they all
 * fall through to the same fallback and their content is dropped. With it,
 * covering one directive means adding one template.
 *
 * A directive that has no template yet keeps the marker the fallback renderer
 * would have left, and its children are still rendered, so nothing is lost
 * while the gap stays greppable.
 *
 * @implements NodeRenderer<GeneralDirectiveNode>
 */
final class GeneralDirectiveNodeRenderer implements NodeRenderer
{
    public function __construct(
        private readonly TemplateRenderer $renderer,
    ) {}

    public function supports(string $nodeFqcn): bool
    {
        return $nodeFqcn === GeneralDirectiveNode::class || is_a($nodeFqcn, GeneralDirectiveNode::class, true);
    }

    public function render(Node $node, RenderContext $renderContext): string
    {
        if ($node instanceof GeneralDirectiveNode === false) {
            throw new InvalidArgumentException('Node must be an instance of ' . GeneralDirectiveNode::class);
        }

        $template = 'body/directive/' . $this->templateName($node->getName()) . '.md.twig';
        if ($this->renderer->isTemplateFound($renderContext, $template)) {
            return $this->renderer->renderTemplate($renderContext, $template, ['node' => $node]);
        }

        $marker = '<!-- TODO: no Markdown rendering for "' . str_replace(['--', '>'], ['- -', ''], $node->getName()) . '" -->';
        $content = '';
        foreach ($node->getChildren() as $child) {
            $content .= $this->renderer->renderTemplate(
                $renderContext,
                'body/directive/general-child.md.twig',
                ['node' => $child],
            );
        }

        return $marker . "\n\n" . $content;
    }

    /** Same mapping the HTML renderer uses, so the template names line up. */
    private function templateName(string $directiveName): string
    {
        $directiveName = str_replace(':', '/', $directiveName);

        return (string) preg_replace('/[^a-zA-Z0-9-_\/]/', '_', $directiveName);
    }
}
