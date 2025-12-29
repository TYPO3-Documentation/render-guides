<?php

namespace T3Docs\Typo3DocsTheme\EventListeners;

use phpDocumentor\Guides\Settings\SettingsManager;
use T3Docs\Typo3DocsTheme\Renderer\DecoratingPlantumlRenderer;

/**
 * Disables HTTP calls that can fail tests
 */
final readonly class TestingModeActivator
{
    public function __construct(
        private SettingsManager $settingsManager,
        private DecoratingPlantumlRenderer $decoratingPlantumlRenderer
    ) {}

    public function __invoke(): void
    {
        // We are in test mode
        if ($this->settingsManager->getProjectSettings()->isFailOnError()) {
            $this->decoratingPlantumlRenderer->setDisabled(true);
        }
    }
}
