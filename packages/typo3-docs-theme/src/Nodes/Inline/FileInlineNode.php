<?php

namespace T3Docs\Typo3DocsTheme\Nodes\Inline;

use phpDocumentor\Guides\Nodes\Inline\AbstractLinkInlineNode;
use phpDocumentor\Guides\Nodes\Inline\CrossReferenceNode;
use phpDocumentor\Guides\Nodes\Inline\PlainTextInlineNode;
use T3Docs\Typo3DocsTheme\ReferenceResolvers\ObjectsInventory\FileObject;

final class FileInlineNode extends AbstractLinkInlineNode implements CrossReferenceNode
{
    public const TYPE = 'file';
    private ?FileObject $fileObject = null;

    public function __construct(
        private string $fileLink,
        private string $fileLabel,
        private string $interlinkDomain,
        private string $linkType
    ) {
        parent::__construct(self::TYPE, $fileLink, $fileLabel, [new PlainTextInlineNode($fileLabel)]);
    }

    public function getFileLink(): string
    {
        return $this->fileLink;
    }

    public function getLinkType(): string
    {
        return $this->linkType;
    }

    public function getFileLabel(): string
    {
        return $this->fileLabel;
    }

    public function setFileLabel(string $fileLabel): void
    {
        $this->fileLabel = $fileLabel;
    }

    public function getFileObject(): ?FileObject
    {
        return $this->fileObject;
    }

    public function setFileObject(?FileObject $fileObject): void
    {
        $this->fileObject = $fileObject;
    }

    public function getInterlinkDomain(): string
    {
        return $this->interlinkDomain;
    }

    public function getInterlinkGroup(): string
    {
        return 'typo3:file';
    }
}
