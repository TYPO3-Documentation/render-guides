<?php

declare(strict_types=1);

namespace T3Docs\Typo3DocsTheme\TextRoles;

use phpDocumentor\Guides\Nodes\Inline\InlineNodeInterface;
use phpDocumentor\Guides\Nodes\Inline\PlainTextInlineNode;
use phpDocumentor\Guides\Nodes\Inline\ReferenceNode;
use phpDocumentor\Guides\ReferenceResolvers\AnchorNormalizer;
use phpDocumentor\Guides\RestructuredText\Parser\DocumentParserContext;
use Psr\Log\LoggerInterface;

final class ViewhelperTextRole extends CustomLinkTextRole
{
    private const TYPE = 'viewhelper';

    /**
     * @see https://regex101.com/r/Fj8X5Y/1
     */
    public function __construct(
        LoggerInterface                   $logger,
        private readonly AnchorNormalizer $anchorNormalizer,
    ) {
        parent::__construct($logger, $anchorNormalizer);
    }

    protected function createNode(DocumentParserContext $documentParserContext, string $referenceTarget, string|null $referenceName, string $role): ReferenceNode
    {
        $children = $referenceName ? [new PlainTextInlineNode($referenceName)] : [];
        if (preg_match(self::INTERLINK_NAME_REGEX, $referenceTarget, $matches)) {
            return $this->createNodeWithInterlink($documentParserContext, $matches[2], $matches[1], $children);
        }
        return $this->createNodeWithInterlink($documentParserContext, $referenceTarget, '', $children);
    }

    /** @param list<InlineNodeInterface> $children */
    private function createNodeWithInterlink(DocumentParserContext $documentParserContext, string $referenceTarget, string $interlinkDomain, array $children): ReferenceNode
    {
        $id = $this->anchorNormalizer->reduceAnchor($referenceTarget);

        return new ReferenceNode($id, $children, $interlinkDomain, 'typo3:' . $this->getName());
    }

    public function getName(): string
    {
        return self::TYPE;
    }
}
