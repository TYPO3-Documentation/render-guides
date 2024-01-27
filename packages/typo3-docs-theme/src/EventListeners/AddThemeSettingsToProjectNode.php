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
        foreach ($this->themeSettings->getAllSettings() as $key => $setting) {
            if (trim($setting) !== '') {
                $projectNode->addVariable($key, new PlainTextInlineNode($setting));
            }
        }
    }
}
