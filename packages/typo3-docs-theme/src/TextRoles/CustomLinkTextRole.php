<?php

declare(strict_types=1);

namespace T3Docs\Typo3DocsTheme\TextRoles;

use phpDocumentor\Guides\Nodes\Inline\AbstractLinkInlineNode;
use phpDocumentor\Guides\Nodes\Inline\ReferenceNode;
use phpDocumentor\Guides\ReferenceResolvers\AnchorNormalizer;
use phpDocumentor\Guides\RestructuredText\Parser\DocumentParserContext;
use phpDocumentor\Guides\RestructuredText\TextRoles\TextRole;
use Psr\Log\LoggerInterface;

abstract class CustomLinkTextRole implements TextRole
{
    /**
     * @see https://regex101.com/r/OyN05v/1
     */
    protected const INTERLINK_NAME_REGEX = '/^([a-zA-Z0-9]+):([^:]+.*$)/';
    /**
     * @see https://regex101.com/r/mqBxQj/1
     */
    protected const TEXTROLE_LINK_REGEX = '/^(.*?)(?:(?:\s|^)<([^<]+)>)?$/s';

    public function __construct(
        protected readonly LoggerInterface $logger,
        private readonly AnchorNormalizer $anchorReducer,
    ) {}

    /**
     * @return list<string>
     */
    public function getAliases(): array
    {
        return [];
    }

    public function processNode(
        DocumentParserContext $documentParserContext,
        string $role,
        string $content,
        string $rawContent,
    ): AbstractLinkInlineNode {
        $parsed = $this->extractEmbeddedUri($rawContent);

        return $this->createNode($documentParserContext, $parsed['uri'], $parsed['text'], $role);
    }

    protected function createNode(DocumentParserContext $documentParserContext, string $referenceTarget, string|null $referenceName, string $role): AbstractLinkInlineNode
    {
        if (preg_match(self::INTERLINK_NAME_REGEX, $referenceTarget, $matches)) {
            $interlinkDomain = $matches[1];
            $id = $this->anchorReducer->reduceAnchor($matches[2]);
        } else {
            $interlinkDomain = '';
            $id = $this->anchorReducer->reduceAnchor($referenceTarget);
        }

        return new ReferenceNode($id, $referenceName ?? '', $interlinkDomain, 'php:' . $this->getName());
    }

    /** @return array{text:?string,uri:string} */
    private function extractEmbeddedUri(string $text): array
    {
        preg_match(self::TEXTROLE_LINK_REGEX, $text, $matches);
        $description = null;
        $uri = $text;
        if (isset($matches[1]) && is_string($matches[1])) {
            $description = $matches[1] === '' ? null : $matches[1];
            $uri = $matches[1];
        }

        if (isset($matches[2]) && is_string($matches[2])) {
            // there is an embedded URI, text and URI are different
            $uri = $matches[2];
        } else {
            $description = null;
        }

        return ['text' => $description, 'uri' => $uri];
    }
}
