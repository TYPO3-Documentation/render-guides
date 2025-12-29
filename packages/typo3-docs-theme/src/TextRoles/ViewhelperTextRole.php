<?php

declare(strict_types=1);

namespace T3Docs\Typo3DocsTheme\TextRoles;

use phpDocumentor\Guides\Nodes\Inline\ReferenceNode;
use phpDocumentor\Guides\RestructuredText\Parser\DocumentParserContext;

final class ViewhelperTextRole extends CustomLinkTextRole
{
    private const string TYPE = 'viewhelper';

    #[\Override]
    protected function createNode(DocumentParserContext $documentParserContext, string $referenceTarget, string|null $referenceName, string $role): ReferenceNode
    {
        if (preg_match(self::INTERLINK_NAME_REGEX, $referenceTarget, $matches)) {
            return $this->createNodeWithInterlink($matches[2], $matches[1], $referenceName);
        }
        return $this->createNodeWithInterlink($referenceTarget, '', $referenceName);
    }

    private function createNodeWithInterlink(string $referenceTarget, string $interlinkDomain, string|null $referenceName): ReferenceNode
    {
        $id = $this->anchorNormalizer->reduceAnchor($referenceTarget);

        return new ReferenceNode($id, $referenceName ?? '', $interlinkDomain, 'typo3:' . $this->getName());
    }

    public function getName(): string
    {
        return self::TYPE;
    }
}
