<?php

namespace T3Docs\Typo3DocsTheme\Inventory;

interface InterlinkParserInterface
{
    public function parse(string $key): ?InterlinkParts;
}
