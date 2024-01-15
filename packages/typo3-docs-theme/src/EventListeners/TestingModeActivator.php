<?php

namespace T3Docs\Typo3DocsTheme\EventListeners;

use phpDocumentor\Guides\Event\PreParseProcess;
use phpDocumentor\Guides\Settings\SettingsManager;
use T3Docs\Typo3DocsTheme\Renderer\DecoratingPlantumlRenderer;

/**
 * Disables HTTP calls that can fail tests
 */
final class TestingModeActivator
{
    public function __construct(
        private readonly SettingsManager $settingsManager,
        private readonly DecoratingPlantumlRenderer $decoratingPlantumlRenderer
    ) {}

    public function __invoke(PreParseProcess $event): void
    {
        // We are in test mode
        if ($this->settingsManager->getProjectSettings()->isFailOnError()) {
            $this->decoratingPlantumlRenderer->setDisabled(true);
        }
    }
}
