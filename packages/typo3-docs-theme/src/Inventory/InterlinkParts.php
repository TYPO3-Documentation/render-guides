<?php

namespace T3Docs\Typo3DocsTheme\Inventory;

final class InterlinkParts
{
    public function __construct(
        public string $normalizedKey, // reduced via AnchorNormalizer
        public string $kind,          // 'core' | 'default' | 'package' | 'legacy-ext'
        public string $vendor,        // e.g. 'typo3' or default inventory name
        public string $package,       // e.g. 'cms-core' or a default-inventory pseudo package
        public ?string $version       // null means "unspecified" (to be resolved later)
    ) {}
}
