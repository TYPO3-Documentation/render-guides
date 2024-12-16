<?php

namespace T3Docs\Typo3DocsTheme\Nodes\Inline;

use phpDocumentor\Guides\Nodes\Inline\InlineNode;
use T3Docs\VersionHandling\Packagist\ComposerPackage;

final class ComposerInlineNode extends InlineNode
{
    public const TYPE = 'code';

    public function __construct(
        private string $composerName,
        private ComposerPackage $package,
    ) {
        parent::__construct(self::TYPE, $composerName);
    }

    public function getComposerName(): string
    {
        return $this->composerName;
    }

    public function getPackage(): ComposerPackage
    {
        return $this->package;
    }
}
