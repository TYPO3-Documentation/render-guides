<?php

namespace T3Docs\Typo3DocsTheme\Nodes;

use phpDocumentor\Guides\Nodes\CollectionNode;
use phpDocumentor\Guides\Nodes\Inline\PlainTextInlineNode;
use phpDocumentor\Guides\Nodes\InlineCompoundNode;
use phpDocumentor\Guides\Nodes\LinkTargetNode;
use phpDocumentor\Guides\Nodes\Node;
use phpDocumentor\Guides\Nodes\PrefixedLinkTargetNode;
use phpDocumentor\Guides\RestructuredText\Nodes\GeneralDirectiveNode;

final class Typo3FileNode extends GeneralDirectiveNode implements LinkTargetNode, PrefixedLinkTargetNode
{
    public const LINK_TYPE = 'typo3:file';
    public const LINK_PREFIX = 'file-';

    /**
     * @param Node[] $description
     */
    public function __construct(
        private readonly string $id,
        private readonly string $fileName,
        private readonly string $language,
        private readonly string $path,
        private readonly string $composerPath = '',
        private readonly string $composerPathPrefix = '',
        private readonly string $classicPath = '',
        private readonly string $classicPathPrefix = '',
        private readonly string $scope = '',
        private readonly string $regex = '',
        private readonly ?CollectionNode $configuration = null,
        private readonly ?CollectionNode $command = null,
        private array $description = [],
        private readonly bool $noindex = false,
        public readonly string $shortDescription = '',
    ) {
        parent::__construct('typo3-file', $fileName, new InlineCompoundNode([new PlainTextInlineNode($fileName)]));
    }

    public function getScope(): string
    {
        return $this->scope;
    }

    public function getFileName(): string
    {
        return $this->fileName;
    }

    public function getLanguage(): string
    {
        return $this->language;
    }

    public function getPath(): string
    {
        return $this->path;
    }

    public function getClassicPath(): string
    {
        return $this->classicPath;
    }

    public function getRegex(): string
    {
        return $this->regex;
    }

    public function getConfiguration(): ?CollectionNode
    {
        return $this->configuration;
    }

    public function getCommand(): ?CollectionNode
    {
        return $this->command;
    }

    /**
     * @return Node[]
     */
    public function getDescription(): array
    {
        return $this->description;
    }

    public function getAnchor(): string
    {
        return self::LINK_PREFIX . $this->id;
    }

    public function isNoindex(): bool
    {
        return $this->noindex;
    }

    public function getPrefix(): string
    {
        return self::LINK_PREFIX;
    }

    public function getLinkType(): string
    {
        return self::LINK_TYPE;
    }

    public function getLinkText(): string
    {
        return $this->getComposerPathPrefix() . $this->getComposerPath() . $this->fileName;
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getComposerPath(): string
    {
        return $this->composerPath;
    }

    public function getComposerPathPrefix(): string
    {
        return $this->composerPathPrefix;
    }

    public function getClassicPathPrefix(): string
    {
        return $this->classicPathPrefix;
    }

    public function getShortDescription(): string
    {
        return $this->shortDescription;
    }

}
