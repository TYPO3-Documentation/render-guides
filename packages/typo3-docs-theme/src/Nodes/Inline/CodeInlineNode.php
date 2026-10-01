<?php

namespace T3Docs\Typo3DocsTheme\Nodes\Inline;

use phpDocumentor\Guides\Nodes\Inline\InlineNode;

final class CodeInlineNode extends InlineNode implements InHeadline
{
    use InHeadlineTrait;

    public const TYPE = 'code';

    /**
     * @param array<string, string> $info
     */
    public function __construct(string $value, private string $language, private string $helpText = '', private array $info = [])
    {
        parent::__construct(self::TYPE, $value);
    }

    public function getLanguage(): string
    {
        return $this->language;
    }

    public function getHelpText(): string
    {
        return $this->helpText;
    }

    /**
     * @return array<string, string>
     */
    public function getInfo(): array
    {
        return $this->info;
    }

    /**
     * What the infobox says, once it is known: a ":fluid:" role learns what
     * its ViewHelper does only after every page is parsed.
     *
     * @param array<string, string> $info
     */
    public function describeAs(string $language, string $helpText, array $info): void
    {
        $this->language = $language;
        $this->helpText = $helpText;
        $this->info = [...$this->info, ...$info];
    }
}
