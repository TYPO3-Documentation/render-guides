<?php

namespace T3Docs\Typo3DocsTheme\Inventory;

interface InventoryUrlBuilderInterface
{
    public function buildUrl(InterlinkParts $parts): ?string;
}
