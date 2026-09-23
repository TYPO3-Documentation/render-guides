<?php

declare(strict_types=1);

namespace T3Docs\Typo3DocsTheme\Tests\Unit\EventListeners;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use phpDocumentor\Guides\Event\PostProjectNodeCreated;
use phpDocumentor\Guides\Nodes\ProjectNode;
use phpDocumentor\Guides\Settings\ProjectSettings;
use T3Docs\Typo3DocsTheme\EventListeners\AddThemeSettingsToProjectNode;
use T3Docs\Typo3DocsTheme\Settings\Typo3DocsThemeSettings;

/**
 * The listener reads the command line out of $_SERVER['argv'] rather than from
 * an InputDefinition, so the options it reacts to are only covered here.
 */
#[CoversClass(AddThemeSettingsToProjectNode::class)]
final class AddThemeSettingsToProjectNodeTest extends TestCase
{
    /** @var array<int, string> */
    private array $originalArgv = [];

    protected function setUp(): void
    {
        $this->originalArgv = (array) ($_SERVER['argv'] ?? []);
    }

    protected function tearDown(): void
    {
        $_SERVER['argv'] = $this->originalArgv;
    }

    public function testSingleMarkdownOptionRendersNothingButTheSingleFile(): void
    {
        $settings = $this->dispatchWith(['bin/guides', '--single-markdown', 'Documentation']);

        self::assertSame(['singlemd'], $settings->getOutputFormats());
    }

    public function testSingleMarkdownOptionOverridesTheConfiguredFormats(): void
    {
        $settings = $this->dispatchWith(
            ['bin/guides', '--single-markdown', 'Documentation'],
            ['html', 'md', 'interlink'],
        );

        self::assertSame(['singlemd'], $settings->getOutputFormats());
    }

    public function testSingleHtmlOptionRendersNothingButTheSingleFile(): void
    {
        $settings = $this->dispatchWith(
            ['bin/guides', '--single-html', 'Documentation'],
            ['html', 'md', 'interlink'],
        );

        self::assertSame(['singlepage'], $settings->getOutputFormats());
    }

    public function testBothSingleFileOptionsRenderBothFiles(): void
    {
        $settings = $this->dispatchWith(
            ['bin/guides', '--single-markdown', '--single-html', 'Documentation'],
            ['html', 'md', 'interlink'],
        );

        self::assertSame(['singlepage', 'singlemd'], $settings->getOutputFormats());
    }

    public function testFormatsAreUntouchedWithoutTheOption(): void
    {
        $settings = $this->dispatchWith(['bin/guides', 'Documentation'], ['html', 'md']);

        self::assertSame(['html', 'md'], $settings->getOutputFormats());
    }

    /**
     * A published render must never produce the single file by accident, so a
     * near miss has to stay a near miss.
     */
    public function testSimilarlyNamedArgumentDoesNotTriggerTheOption(): void
    {
        $settings = $this->dispatchWith(
            ['bin/guides', '--single-markdown-please', 'Documentation'],
            ['html'],
        );

        self::assertSame(['html'], $settings->getOutputFormats());

        $settings = $this->dispatchWith(
            ['bin/guides', '--single-html-please', 'Documentation'],
            ['html'],
        );

        self::assertSame(['html'], $settings->getOutputFormats());
    }

    public function testMinimalTestOptionStillWins(): void
    {
        $settings = $this->dispatchWith(['bin/guides', '--minimal-test', 'Documentation'], ['html']);

        self::assertSame(['singlepage'], $settings->getOutputFormats());
    }

    /**
     * @param array<int, string> $argv
     * @param array<int, string> $outputFormats
     */
    private function dispatchWith(array $argv, array $outputFormats = ['html']): ProjectSettings
    {
        $_SERVER['argv'] = $argv;

        $settings = new ProjectSettings();
        $settings->setOutputFormats($outputFormats);

        $listener = new AddThemeSettingsToProjectNode(new Typo3DocsThemeSettings([]));
        $listener(new PostProjectNodeCreated(new ProjectNode(), $settings));

        return $settings;
    }
}
