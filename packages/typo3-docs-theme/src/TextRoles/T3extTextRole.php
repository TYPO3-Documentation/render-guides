<?php

namespace T3Docs\Typo3DocsTheme\TextRoles;

use phpDocumentor\Guides\Nodes\Inline\HyperLinkNode;
use phpDocumentor\Guides\RestructuredText\Parser\DocumentParserContext;
use phpDocumentor\Guides\RestructuredText\Parser\References\EmbeddedReferenceParser;
use phpDocumentor\Guides\RestructuredText\TextRoles\TextRole;

final class T3extTextRole implements TextRole
{
    use EmbeddedReferenceParser;
    final public const NAME = 't3ext';

    public function getName(): string
    {
        return self::NAME;
    }

    /** @inheritDoc */
    public function getAliases(): array
    {
        return [];
    }

    public function processNode(
        DocumentParserContext $documentParserContext,
        string $role,
        string $content,
        string $rawContent,
    ): HyperLinkNode {
        $referenceData = $this->extractEmbeddedReference($content);

        return $this->createNode($documentParserContext, $referenceData->reference, $referenceData->text, $role);
    }

    protected function createNode(
        DocumentParserContext $documentParserContext,
        string $referenceTarget,
        string|null $referenceName,
        string $role
    ): HyperLinkNode {
        $extKey = $referenceTarget;
        if (str_starts_with($extKey, 'EXT:')) {
            $extKey = str_replace('EXT:', '', $extKey);
        }
        $terLink = sprintf('https://extensions.typo3.org/extension/%s', $extKey);
        $extName = $referenceName ?? 'EXT:' . $extKey;
        return new HyperLinkNode($extName, $terLink);
    }
}
