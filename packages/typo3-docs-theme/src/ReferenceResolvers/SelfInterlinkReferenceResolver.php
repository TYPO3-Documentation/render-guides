<?php

declare(strict_types=1);

namespace T3Docs\Typo3DocsTheme\ReferenceResolvers;

use phpDocumentor\Guides\Nodes\Inline\CrossReferenceNode;
use phpDocumentor\Guides\Nodes\Inline\DocReferenceNode;
use phpDocumentor\Guides\Nodes\Inline\LinkInlineNode;
use phpDocumentor\Guides\Nodes\Inline\ReferenceNode;
use phpDocumentor\Guides\ReferenceResolvers\AnchorReferenceResolver;
use phpDocumentor\Guides\ReferenceResolvers\DocReferenceResolver;
use phpDocumentor\Guides\ReferenceResolvers\InterlinkReferenceResolver;
use phpDocumentor\Guides\ReferenceResolvers\Messages;
use phpDocumentor\Guides\ReferenceResolvers\ReferenceResolver;
use phpDocumentor\Guides\RenderContext;
use T3Docs\Typo3DocsTheme\Inventory\Typo3InventoryRepository;
use T3Docs\Typo3DocsTheme\Permalinks\Permalinks;
use T3Docs\Typo3DocsTheme\Sitemap\ManualAddress;

use function count;
use function str_starts_with;
use function strtolower;

/**
 * Resolves a link of a manual to itself within the render, never by the
 * inventory the manual has published.
 *
 * That inventory is the one of the published version: it lacks what a change
 * adds, and it is briefly missing while that version is deployed, which fails
 * every render that asks for it at that moment. The compiler already drops
 * the shortcode from a link it sees, @see
 * RemoveInterlinkSelfReferencesFromCrossReferenceNodeTransformer. This also
 * catches the links it does not see, such as one in the description of a
 * ".. typo3:file::", and a link that names the version being rendered, as
 * "t3coreapi/main:" does in a render of main. A link to another version of
 * the manual goes to the inventory of that version, as any interlink does.
 *
 * It takes the place of guides' interlink resolver and hands every other link
 * to it: a link to the manual itself that the render does not know fails as
 * a local link does, without asking the published inventory.
 */
final class SelfInterlinkReferenceResolver implements ReferenceResolver
{
    public function __construct(
        private readonly InterlinkReferenceResolver $interlinkResolver,
        private readonly AnchorReferenceResolver $anchorResolver,
        private readonly DocReferenceResolver $docResolver,
        private readonly Permalinks $permalinks,
        private readonly ManualAddress $manualAddress,
        private readonly Typo3InventoryRepository $inventoryRepository,
    ) {}

    public function resolve(LinkInlineNode $node, RenderContext $renderContext, Messages $messages): bool
    {
        $local = match (true) {
            !$node instanceof CrossReferenceNode || !$this->namesThisManual($node->getInterlinkDomain(), $renderContext) => null,
            $node instanceof ReferenceNode => new ReferenceNode($node->getTargetReference(), [], '', $node->getLinkType(), $node->getPrefix()),
            $node instanceof DocReferenceNode => new DocReferenceNode($node->getTargetReference()),
            default => null,
        };
        if ($local === null) {
            return $this->interlinkResolver->resolve($node, $renderContext, $messages);
        }

        $resolver = $local instanceof DocReferenceNode ? $this->docResolver : $this->anchorResolver;
        if (!$resolver->resolve($local, $renderContext, $messages)) {
            return false;
        }

        $node->setUrl($local->getUrl());
        if (count($node->getChildren()) === 0) {
            foreach ($local->getChildren() as $child) {
                $node->addChildNode($child);
            }
        }

        return true;
    }

    /**
     * Whether an interlink names the manual being rendered: by its shortcode
     * alone, or by its shortcode and the version being rendered. Versions are
     * compared by where they are published, so "13" and "13.4" are one.
     */
    private function namesThisManual(string $domain, RenderContext $renderContext): bool
    {
        $shortcode = strtolower($this->permalinks->shortcode());
        $domain = strtolower($domain);
        if ($shortcode === '' || $domain === '') {
            return false;
        }

        if ($domain === $shortcode) {
            return true;
        }

        if (!str_starts_with($domain, $shortcode . '/')) {
            return false;
        }

        $address = $this->manualAddress->of($renderContext->getProjectNode()->getVersion());

        return $address !== null && $this->inventoryRepository->previewUrl($domain) === $address;
    }

    public static function getPriority(): int
    {
        return InterlinkReferenceResolver::PRIORITY;
    }
}
