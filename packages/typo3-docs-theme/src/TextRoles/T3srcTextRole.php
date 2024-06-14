<?php

namespace T3Docs\Typo3DocsTheme\TextRoles;

use phpDocumentor\Guides\Nodes\Inline\HyperLinkNode;
use phpDocumentor\Guides\RestructuredText\Parser\DocumentParserContext;
use phpDocumentor\Guides\RestructuredText\Parser\References\EmbeddedReferenceParser;
use phpDocumentor\Guides\RestructuredText\TextRoles\TextRole;
use T3Docs\Typo3DocsTheme\Inventory\Typo3VersionService;

final class T3srcTextRole implements TextRole
{
    use EmbeddedReferenceParser;
    final public const NAME = 't3src';

    public function __construct(
        private readonly Typo3VersionService $typo3VersionService,
    ) {}

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
        $typo3Version = $this->typo3VersionService->getPreferredVersion();
        $fileLink = $referenceTarget;
        if (str_starts_with($fileLink, 'EXT:')) {
            $fileLink = str_replace('EXT:', 'typo3/sysext/', $fileLink);
        }
        if (!str_starts_with($fileLink, 'typo3/sysext/')) {
            $fileLink = 'typo3/sysext/' . $fileLink;
        }
        $gitHubLink = sprintf('https://github.com/typo3/typo3/blob/%s/%s', $typo3Version, $fileLink);
        $fileName = $referenceName ?? str_replace('typo3/sysext/', 'EXT:', $fileLink) . ' (GitHub)';
        return new HyperLinkNode($fileName, $gitHubLink);
    }
}
