<?php

namespace T3Docs\Typo3DocsTheme\Nodes;

use phpDocumentor\Guides\Nodes\Inline\PlainTextInlineNode;
use phpDocumentor\Guides\Nodes\InlineCompoundNode;
use phpDocumentor\Guides\Nodes\LinkTargetNode;
use phpDocumentor\Guides\Nodes\Node;
use phpDocumentor\Guides\Nodes\OptionalLinkTargetsNode;
use phpDocumentor\Guides\Nodes\PrefixedLinkTargetNode;
use phpDocumentor\Guides\RestructuredText\Nodes\GeneralDirectiveNode;

final class ViewHelperNode extends GeneralDirectiveNode implements LinkTargetNode, OptionalLinkTargetsNode, PrefixedLinkTargetNode
{
    public const LINK_TYPE = 'typo3:viewhelper';
    public const LINK_PREFIX = 'viewhelper-';
    /**
     * @param Node[] $documentation
     * @param Node[] $description
     * @param Node[] $sections
     * @param Node[] $examples
     * @param array<string, string> $docTags
     * @param string[] $display
     * @param array<string, ViewHelperArgumentNode> $arguments
     */
    public function __construct(
        private readonly string $id,
        private readonly string $tagName,
        private readonly string $shortClassName,
        private readonly string $namespace,
        private readonly string $className,
        private readonly array $documentation,
        private readonly array $description,
        private readonly array $sections,
        private readonly array $examples,
        private readonly string $xmlNamespace,
        private readonly bool $allowsArbitraryArguments,
        private readonly array $docTags,
        private readonly string $gitHubLink = '',
        private readonly bool $noindex = false,
        private readonly array $display = [],
        private array $arguments = [],
    ) {
        parent::__construct('viewhelper', $tagName, new InlineCompoundNode([new PlainTextInlineNode($tagName)]), array_values($documentation));
    }

    /**
     * @return Node[]
     */
    public function getSections(): array
    {
        return $this->sections;
    }

    /**
     * @return Node[]
     */
    public function getExamples(): array
    {
        return $this->examples;
    }

    /**
     * @return string[]
     */
    public function getDisplay(): array
    {
        return $this->display;
    }

    /**
     * @return Node[]
     */
    public function getDescription(): array
    {
        return $this->description;
    }

    public function getTagName(): string
    {
        return $this->tagName;
    }

    public function getShortClassName(): string
    {
        return $this->shortClassName;
    }

    public function getNamespace(): string
    {
        return $this->namespace;
    }

    public function getClassName(): string
    {
        return $this->className;
    }

    /**
     * @return Node[]
     */
    public function getDocumentation(): array
    {
        return $this->documentation;
    }

    public function getXmlNamespace(): string
    {
        return $this->xmlNamespace;
    }

    public function isAllowsArbitraryArguments(): bool
    {
        return $this->allowsArbitraryArguments;
    }

    /**
     * @return string[]
     */
    public function getDocTags(): array
    {
        return $this->docTags;
    }

    /**
     * @return ViewHelperArgumentNode[]
     */
    public function getArguments(): array
    {
        return $this->arguments;
    }

    /**
     * @param array<string, ViewHelperArgumentNode> $arguments
     */
    public function setArguments(array $arguments): void
    {
        $this->arguments = $arguments;
    }

    public function getLinkType(): string
    {
        return self::LINK_TYPE;
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getLinkText(): string
    {
        return $this->tagName;
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

    public function getGitHubLink(): string
    {
        return $this->gitHubLink;
    }
}
