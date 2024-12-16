<?php

namespace T3Docs\Typo3DocsTheme\TextRoles;

use phpDocumentor\Guides\Nodes\Inline\InlineNode;
use phpDocumentor\Guides\Nodes\Inline\PlainTextInlineNode;
use phpDocumentor\Guides\RestructuredText\Parser\DocumentParserContext;
use phpDocumentor\Guides\RestructuredText\TextRoles\TextRole;
use Psr\Log\LoggerInterface;
use T3Docs\Typo3DocsTheme\Nodes\Inline\ComposerInlineNode;
use T3Docs\VersionHandling\Packagist\PackagistService;

final class ComposerTextRole implements TextRole
{
    public function __construct(
        private readonly PackagistService $packagistService,
        private readonly LoggerInterface $logger,
    ) {}

    public function getName(): string
    {
        return 'composer';
    }

    public function getAliases(): array
    {
        return [];
    }

    public function isValidComposerName(string $name): bool
    {
        $pattern = '/^[a-z0-9_]+(?:[-_][a-z0-9_]+)*\/[a-z0-9_]+(?:[-_][a-z0-9_]+)*$/';
        return preg_match($pattern, $name) === 1;
    }

    public function processNode(DocumentParserContext $documentParserContext, string $role, string $content, string $rawContent): InlineNode
    {
        $composerName = strtolower(trim($content));
        if (!$this->isValidComposerName($composerName)) {
            $this->logger->warning(sprintf('"%s" is not a valid composer name. ', $composerName), $documentParserContext->getLoggerInformation());
            return new PlainTextInlineNode($composerName);
        }
        $composerPackage = $this->packagistService->getComposerInfo($composerName);
        if ($composerPackage->getPackagistStatus() !== 'found') {
            $this->logger->warning(sprintf('"%s" was not found on packagist. ', $composerName), $documentParserContext->getLoggerInformation());
            return new PlainTextInlineNode($composerName);
        }
        return new ComposerInlineNode($composerName, $composerPackage);
    }
}
