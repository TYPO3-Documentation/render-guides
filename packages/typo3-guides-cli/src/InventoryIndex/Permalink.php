<?php

declare(strict_types=1);

namespace T3Docs\GuidesCli\InventoryIndex;

/**
 * One entry of an objects.inv.json inventory: the anchor a permalink names,
 * the role it is filed under, and the address it resolves to.
 */
final class Permalink
{
    public function __construct(
        public readonly string $role,
        public readonly string $anchor,
        public readonly string $target,
    ) {}
}
