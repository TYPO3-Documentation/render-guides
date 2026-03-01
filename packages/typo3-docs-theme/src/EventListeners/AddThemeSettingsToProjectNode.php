<?php

declare(strict_types=1);

namespace T3Docs\Typo3DocsTheme\EventListeners;

use phpDocumentor\Guides\Event\PostProjectNodeCreated;
use phpDocumentor\Guides\Nodes\Inline\PlainTextInlineNode;
use T3Docs\Typo3DocsTheme\Settings\Typo3DocsThemeSettings;

final class AddThemeSettingsToProjectNode
{
    public function __construct(
        private readonly Typo3DocsThemeSettings $themeSettings,
    ) {}

    public function __invoke(PostProjectNodeCreated $event): void
    {
        $projectNode = $event->getProjectNode();

        // Native parsing of argv because we do not have the original ArgvInput
        // available, and neither the InputDefinition. That's ok for the
        // very basic parsing of a global option.
        $argv = (array) ($_SERVER['argv'] ?? []);
        if (in_array('--minimal-test', $argv, true)) {
            $settings = $event->getSettings();

            // Set up input arguments for our minimal test. Will override
            // other input arguments. Can be extended later, so we have
            // control also in the further command flow.
            $settings->setOutputFormats(['singlepage']);
            $settings->setFailOnError('warning'); // 'error' for "no warnings"
        }

        foreach ($this->themeSettings->getAllSettings() as $key => $setting) {
            if (trim($setting) !== '') {
                $projectNode->addVariable($key, new PlainTextInlineNode($setting));
            }
        }
    }
}
