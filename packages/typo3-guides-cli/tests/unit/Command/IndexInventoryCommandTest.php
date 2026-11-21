<?php

declare(strict_types=1);

namespace T3Docs\GuidesCli\Tests\Command;

use PDO;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;
use T3Docs\GuidesCli\Command\IndexInventoryCommand;

use function bin2hex;
use function file_put_contents;
use function is_dir;
use function json_encode;
use function mkdir;
use function random_bytes;
use function rmdir;
use function scandir;
use function sys_get_temp_dir;
use function unlink;

final class IndexInventoryCommandTest extends TestCase
{
    private CommandTester $commandTester;

    private string $directory;

    protected function setUp(): void
    {
        $this->commandTester = new CommandTester(new IndexInventoryCommand());
        $this->directory = sys_get_temp_dir() . '/typo3-guides-inventory-' . bin2hex(random_bytes(6));
        mkdir($this->directory);
    }

    protected function tearDown(): void
    {
        $this->removeDirectory($this->directory);
    }

    #[Test]
    public function indexesAnInventoryIntoANewDatabase(): void
    {
        $inventory = $this->directory . '/objects.inv.json';
        file_put_contents($inventory, (string) json_encode([
            'std:label' => [
                'start' => ['Render Guides', 'main', 'Index.html#start', 'Render Guides'],
            ],
        ]));
        $database = $this->directory . '/var/docs.sqlite';

        $this->commandTester->execute([
            'inventory' => $inventory,
            '--database' => $database,
            '--repository' => 't3docs/render-guides',
            '--url' => 'https://docs.typo3.org/other/t3docs/render-guides/main/en-us/',
            '--version' => 'main',
            '--language' => 'en-us',
        ]);

        $this->commandTester->assertCommandIsSuccessful();
        self::assertStringContainsString('Indexed 1 permalinks of t3docs/render-guides main (en-us)', $this->commandTester->getDisplay());

        $connection = new PDO('sqlite:' . $database);
        self::assertSame('Index.html#start', $connection->query('SELECT target FROM permalinks')->fetchColumn());
    }

    private function removeDirectory(string $path): void
    {
        foreach (scandir($path) ?: [] as $file) {
            if ($file === '.' || $file === '..') {
                continue;
            }

            $child = $path . '/' . $file;
            if (is_dir($child)) {
                $this->removeDirectory($child);
            } else {
                unlink($child);
            }
        }

        rmdir($path);
    }

    #[Test]
    public function failsWithoutADatabase(): void
    {
        $this->commandTester->execute([
            'inventory' => $this->directory . '/objects.inv.json',
            '--repository' => 't3docs/render-guides',
            '--url' => 'https://docs.typo3.org/other/t3docs/render-guides/main/en-us/',
            '--version' => 'main',
            '--language' => 'en-us',
        ]);

        self::assertSame(Command::FAILURE, $this->commandTester->getStatusCode());
        self::assertStringContainsString('The "database" option is required.', $this->commandTester->getDisplay());
    }

    #[Test]
    public function failsOnAnInvalidInventory(): void
    {
        $inventory = $this->directory . '/objects.inv.json';
        file_put_contents($inventory, 'not json');

        $this->commandTester->execute([
            'inventory' => $inventory,
            '--database' => $this->directory . '/docs.sqlite',
            '--repository' => 't3docs/render-guides',
            '--url' => 'https://docs.typo3.org/other/t3docs/render-guides/main/en-us/',
            '--version' => 'main',
            '--language' => 'en-us',
        ]);

        self::assertSame(Command::FAILURE, $this->commandTester->getStatusCode());
        self::assertStringContainsString('is not valid JSON', $this->commandTester->getDisplay());
    }
}
