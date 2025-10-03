<?php

namespace T3Docs\Typo3DocsTheme\Inventory;

use T3Docs\VersionHandling\DefaultInventories;

final class DefaultInventoryUrlBuilder implements InventoryUrlBuilderInterface
{
    public function __construct(
        private readonly Typo3VersionService $versions
    ) {}

    public function buildUrl(InterlinkParts $parts): ?string
    {
        switch ($parts->kind) {
            case 'core':
            case 'legacy-ext':
                $resolved = $parts->version ?? $this->versions->getPreferredVersion();
                $resolved = $this->versions->resolveCoreVersion($resolved);
                if ($parts->package === 'cms-core') {
                    $resolved = 'main';
                }
                return \sprintf(
                    'https://docs.typo3.org/c/%s/%s/%s/en-us/',
                    $parts->vendor,
                    $parts->package,
                    $resolved
                );

            case 'default':
                $di = DefaultInventories::tryFrom($parts->package);
                if (!$di) {
                    return null;
                }
                $resolved  = $this->versions->resolveCoreVersion($parts->version ?? 'stable');
                return $di->getUrl($resolved);

            case 'package':
                $resolved = $parts->version ?? 'main';
                $resolved = $this->versions->resolveVersion($resolved);
                return \sprintf(
                    'https://docs.typo3.org/p/%s/%s/%s/en-us/',
                    $parts->vendor,
                    $parts->package,
                    $resolved
                );
        }
        return null;
    }
}
