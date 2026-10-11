<?php

declare(strict_types=1);

namespace T3Docs\Typo3DocsTheme\EventListeners;

use phpDocumentor\Guides\Event\PostProjectNodeCreated;
use T3Docs\Typo3DocsTheme\Settings\ComposerPackage;
use T3Docs\Typo3DocsTheme\Settings\Typo3DocsThemeSettings;

/**
 * Uses the Composer name of an extension as its interlink shortcode when
 * guides.xml declares none.
 *
 * docs.typo3.org resolves the permalinks of an extension by its Composer name,
 * "georgringer/news:start", so that name is the right shortcode. The manuals
 * in "m/" and other packages have shortcodes of their own, such as
 * "t3coreapi", and the resolver does not know their Composer names: they keep
 * the warning.
 *
 * Registered before AddThemeSettingsToProjectNode, which copies the settings
 * into the project node.
 */
final class InterlinkShortcodeFromComposer
{
    public function __construct(
        private readonly Typo3DocsThemeSettings $themeSettings,
    ) {}

    public function __invoke(PostProjectNodeCreated $event): void
    {
        if ($this->themeSettings->getSettings('interlink_shortcode') !== '') {
            return;
        }

        $package = ComposerPackage::findFor($event->getSettings()->getInput());
        if ($package?->isExtension() === true) {
            $this->themeSettings->setFallback('interlink_shortcode', $package->name);
        }
    }
}
