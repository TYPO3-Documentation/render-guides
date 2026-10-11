<?php

declare(strict_types=1);

namespace T3Docs\Typo3DocsTheme\Integration;

use phpDocumentor\Guides\Cli\Command\Run;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Process\Process;
use T3Docs\GuidesExtension\Command\DefaultConfigDirectory;
use T3Docs\Typo3DocsTheme\ApplicationTestCase;

/**
 * Without "--config", bin/guides loads the guides.xml of the directory it
 * renders.
 */
final class DefaultConfigDirectoryTest extends ApplicationTestCase
{
    private string $tempDir = '';

    protected function setUp(): void
    {
        $this->tempDir = sys_get_temp_dir() . '/render-guides-default-config-' . uniqid();
        foreach (['Documentation', 'Docs', 'NoConfig'] as $directory) {
            mkdir($this->tempDir . '/' . $directory, 0o755, true);
            file_put_contents($this->tempDir . '/' . $directory . '/Index.rst', "Title\n=====\n\nText.\n");
        }

        foreach (['Documentation' => 'From Documentation', 'Docs' => 'From Docs'] as $directory => $title) {
            file_put_contents($this->tempDir . '/' . $directory . '/guides.xml', <<<XML
                <?xml version="1.0" encoding="UTF-8" ?>
                <guides xmlns="https://www.phpdoc.org/guides">
                    <extension class="\T3Docs\Typo3DocsTheme\DependencyInjection\Typo3DocsThemeExtension"/>
                    <project title="{$title}"/>
                </guides>
                XML);
        }
    }

    protected function tearDown(): void
    {
        if (is_dir($this->tempDir)) {
            system('rm -rf ' . escapeshellarg($this->tempDir));
        }
    }

    /** @return iterable<string, array{list<string>, string|null}> */
    public static function commandLines(): iterable
    {
        yield 'no input renders Documentation' => [['guides'], 'Documentation'];
        yield 'run without input renders Documentation' => [['guides', 'run'], 'Documentation'];
        yield 'an input directory' => [['guides', 'run', 'Docs'], 'Docs'];
        yield 'an input directory without "run"' => [['guides', 'Docs'], 'Docs'];
        yield 'an option value of its own is not the input' => [['guides', 'run', '--output', 'NoConfig', 'Docs'], 'Docs'];
        yield 'an option with "=" is not the input' => [['guides', '--output=NoConfig', 'Docs', '--no-progress'], 'Docs'];
        yield 'a flag does not take the input' => [['guides', '--minimal-test', 'Docs'], 'Docs'];
        yield 'an input after "--"' => [['guides', 'run', '--', 'Docs'], 'Docs'];
        yield 'an input without guides.xml' => [['guides', 'run', 'NoConfig'], null];
        yield 'a missing input' => [['guides', 'run', 'Missing'], null];
        yield 'an explicit --config wins' => [['guides', 'run', '--config=NoConfig', 'Docs'], null];
        yield 'an explicit --config as two tokens wins' => [['guides', 'run', '--config', 'NoConfig', 'Docs'], null];
        yield 'an explicit -c wins' => [['guides', 'run', '-c', 'NoConfig', 'Docs'], null];
    }

    /** @param list<string> $argv */
    #[DataProvider('commandLines')]
    public function testFindsTheDirectoryToLoadGuidesXmlFrom(array $argv, ?string $expected): void
    {
        self::assertSame(
            $expected === null ? null : $this->tempDir . '/' . $expected,
            DefaultConfigDirectory::find($argv, $this->tempDir),
        );
    }

    public function testFindsAnAbsoluteInputDirectory(): void
    {
        self::assertSame(
            $this->tempDir . '/Docs',
            DefaultConfigDirectory::find(['guides', $this->tempDir . '/Docs'], '/somewhere/else'),
        );
    }

    /**
     * An option whose value follows as a token of its own and is missing from
     * the list makes the value look like the input directory.
     */
    public function testKnowsEveryOptionOfRunThatTakesAValue(): void
    {
        $command = $this->getContainer()->get(Run::class);
        self::assertInstanceOf(Command::class, $command);
        $command->mergeApplicationDefinition();

        foreach ($command->getDefinition()->getOptions() as $option) {
            if (!$option->acceptValue() || $option->isNegatable()) {
                continue;
            }

            self::assertContains('--' . $option->getName(), DefaultConfigDirectory::VALUE_OPTIONS);
            foreach (array_filter(explode('|', (string) $option->getShortcut())) as $shortcut) {
                self::assertContains('-' . $shortcut, DefaultConfigDirectory::VALUE_OPTIONS);
            }
        }
    }

    public function testRendersWithTheGuidesXmlOfTheInputDirectory(): void
    {
        $process = new Process(
            ['php', dirname(__DIR__, 2) . '/bin/guides', 'run', 'Docs', '--output=' . $this->tempDir . '/out', '--no-progress'],
            $this->tempDir,
        );
        $process->run();

        self::assertSame(0, $process->getExitCode(), $process->getErrorOutput() . $process->getOutput());
        self::assertStringContainsString('From Docs', (string) file_get_contents($this->tempDir . '/out/Index.html'));
    }
}
