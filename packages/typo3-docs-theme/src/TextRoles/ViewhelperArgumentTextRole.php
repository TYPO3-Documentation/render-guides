<?php

declare(strict_types=1);

namespace T3Docs\Typo3DocsTheme\TextRoles;

use phpDocumentor\Guides\Nodes\Inline\ReferenceNode;
use phpDocumentor\Guides\ReferenceResolvers\AnchorNormalizer;
use phpDocumentor\Guides\RestructuredText\Parser\DocumentParserContext;
use Psr\Log\LoggerInterface;

final class ViewhelperArgumentTextRole extends CustomLinkTextRole
{
    private const TYPE = 'viewhelper-argument';
    public function __construct(
        LoggerInterface                   $logger,
        private readonly AnchorNormalizer $anchorNormalizer,
    ) {
        parent::__construct($logger, $anchorNormalizer);
    }

    protected function createNode(DocumentParserContext $documentParserContext, string $referenceTarget, string|null $referenceName, string $role): ReferenceNode
    {
        if (preg_match(self::INTERLINK_NAME_REGEX, $referenceTarget, $matches)) {
            return $this->createNodeWithInterlink($documentParserContext, $matches[2], $matches[1], $referenceName);
        }
        return $this->createNodeWithInterlink($documentParserContext, $referenceTarget, '', $referenceName);
    }

    private function createNodeWithInterlink(DocumentParserContext $documentParserContext, string $referenceTarget, string $interlinkDomain, string|null $referenceName): ReferenceNode
    {
        $id = $this->anchorNormalizer->reduceAnchor($referenceTarget);

        return new ReferenceNode($id, $referenceName ?? '', $interlinkDomain, 'typo3:' . $this->getName());
    }

    public function getName(): string
    {
        return self::TYPE;
    }
}
