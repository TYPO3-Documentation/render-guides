<?php

namespace T3Docs\Typo3DocsTheme\Nodes;

use phpDocumentor\Guides\Nodes\InlineCompoundNode;
use phpDocumentor\Guides\Nodes\LinkTargetNode;
use phpDocumentor\Guides\Nodes\Node;
use phpDocumentor\Guides\Nodes\OptionalLinkTargetsNode;
use phpDocumentor\Guides\Nodes\PrefixedLinkTargetNode;
use phpDocumentor\Guides\RestructuredText\Nodes\ConfvalNode;
use phpDocumentor\Guides\RestructuredText\Nodes\GeneralDirectiveNode;

final class ConfvalMenuNode extends GeneralDirectiveNode implements LinkTargetNode, OptionalLinkTargetsNode, PrefixedLinkTargetNode
{
    public const LINK_TYPE = 'std:confval-menu';
    public const LINK_PREFIX = 'confval-menu-';
    /**
     * @param list<Node> $value
     * @param Node[] $value
     * @param ConfvalNode[] $confvals
     * @param array<string, array<string, int>> $fields
     * @param string[] $exclude
     */
    public function __construct(
        private readonly string $id,
        protected readonly string $plainContent,
        protected readonly InlineCompoundNode $content,
        array $value = [],
        private readonly string $caption = '',
        private array $confvals = [],
        private readonly array $fields = [],
        private readonly string $display = 'tree',
        private readonly bool $excludeNoindex = false,
        private readonly array $exclude = [],
        private readonly bool $noindex = false,
        private readonly string $facet = 'Option',
    ) {
        parent::__construct('confval-menu', $plainContent, $content, array_values($value));
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getCaption(): string
    {
        return $this->caption;
    }

    /**
     * @return ConfvalNode[]
     */
    public function getConfvals(): array
    {
        return $this->confvals;
    }

    /**
     * @param ConfvalNode[] $confvals
     */
    public function setConfvals(array $confvals): void
    {
        $this->confvals = $confvals;
    }

    /**
     * @return array<string, array<string, int>>
     */
    public function getFields(): array
    {
        return $this->fields;
    }

    public function getDisplay(): string
    {
        return $this->display;
    }

    public function isExcludeNoindex(): bool
    {
        return $this->excludeNoindex;
    }

    /**
     * @return string[]
     */
    public function getExclude(): array
    {
        return $this->exclude;
    }

    public function getLinkType(): string
    {
        return self::LINK_TYPE;
    }

    public function getLinkText(): string
    {
        return $this->caption;
    }

    public function getPrefix(): string
    {
        return self::LINK_PREFIX;
    }
    public function getAnchor(): string
    {
        return $this->getPrefix() . $this->getId();
    }

    public function isNoindex(): bool
    {
        return $this->noindex;
    }

    public function getFacet(): string
    {
        return $this->facet;
    }
}
