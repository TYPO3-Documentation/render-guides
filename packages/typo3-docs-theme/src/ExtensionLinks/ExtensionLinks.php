<?php

declare(strict_types=1);

namespace T3Docs\Typo3DocsTheme\ExtensionLinks;

use phpDocumentor\Guides\Settings\SettingsManager;
use T3Docs\Typo3DocsTheme\Api\TerService;
use T3Docs\Typo3DocsTheme\Settings\ComposerPackage;
use T3Docs\Typo3DocsTheme\Settings\Typo3DocsThemeSettings;
use T3Docs\VersionHandling\Packagist\PackagistService;

use function preg_match;
use function str_starts_with;

/**
 * Where else an extension is found: Packagist, the TER, and its repository.
 * A reader who lands on a manual from a search has no other way there.
 *
 * Everything is derived from the composer.json beside the documentation, so
 * guides.xml needs no setting for it. A link is only offered where it leads
 * somewhere: Packagist and the TER are asked whether they know the package.
 */
final class ExtensionLinks
{
    /** @var array{packagist: string, ter: string, repository: string}|null */
    private ?array $links = null;

    public function __construct(
        private readonly SettingsManager $settingsManager,
        private readonly Typo3DocsThemeSettings $themeSettings,
        private readonly PackagistService $packagistService,
        private readonly TerService $terService,
    ) {}

    /** @return array{packagist: string, ter: string, repository: string} */
    public function getLinks(): array
    {
        return $this->links ??= $this->findLinks();
    }

    /** @return array{packagist: string, ter: string, repository: string} */
    private function findLinks(): array
    {
        $links = ['packagist' => '', 'ter' => '', 'repository' => ''];
        $package = ComposerPackage::findFor($this->settingsManager->getProjectSettings()->getInput());
        if ($package === null || !$package->isExtension()) {
            return $links;
        }

        $composerInfo = $this->packagistService->getComposerInfo($package->name);
        if ($composerInfo->getPackagistStatus() === 'found') {
            $links['packagist'] = $composerInfo->getPackagistLink();
        }

        // A system extension is part of the Core, not published in the TER
        if ($package->isThirdPartyExtension() && $package->extensionKey !== '' && $this->terService->isPublished($package->extensionKey)) {
            $links['ter'] = $this->terService->getUrl($package->extensionKey);
        }

        $github = $this->themeSettings->getSettings('edit_on_github');
        if (str_starts_with($package->source, 'https://')) {
            $links['repository'] = $package->source;
        } elseif (preg_match('#^[\w.-]+/[\w.-]+$#', $github) === 1) {
            $links['repository'] = 'https://github.com/' . $github;
        }

        return $links;
    }
}
