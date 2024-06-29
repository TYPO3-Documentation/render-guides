<?php

namespace T3Docs\Typo3DocsTheme\Nodes;

use phpDocumentor\Guides\Nodes\Inline\PlainTextInlineNode;
use phpDocumentor\Guides\Nodes\InlineCompoundNode;
use phpDocumentor\Guides\Nodes\LinkTargetNode;
use phpDocumentor\Guides\Nodes\OptionalLinkTargetsNode;
use phpDocumentor\Guides\Nodes\PrefixedLinkTargetNode;
use phpDocumentor\Guides\RestructuredText\Nodes\GeneralDirectiveNode;

final class ViewHelperArgumentNode extends GeneralDirectiveNode implements LinkTargetNode, OptionalLinkTargetsNode, PrefixedLinkTargetNode
{
    public const LINK_TYPE = 'typo3:viewhelper-argument';
    public const LINK_PREFIX = 'viewhelper-argument-';

    public function __construct(
        private readonly ViewHelperNode $viewHelper,
        private readonly string $id,
        private readonly string $argumentName,
        private readonly string $type,
        private readonly string $description,
        private readonly bool $required,
        private readonly ?string $defaultValue,
        private readonly bool $noindex = false,
    ) {
        parent::__construct('viewhelper-argument', $argumentName, new InlineCompoundNode([new PlainTextInlineNode($argumentName)]));
    }

    public function getArgumentName(): string
    {
        return $this->argumentName;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function isRequired(): bool
    {
        return $this->required;
    }

    public function getDefaultValue(): mixed
    {
        return $this->defaultValue;
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
        return $this->argumentName;
    }

    public function getViewHelper(): ViewHelperNode
    {
        return $this->viewHelper;
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
}
