<?php

declare(strict_types=1);

namespace T3Docs\Typo3DocsTheme\Integration;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Process\Process;

/**
 * Tests error handling for invalid guides.xml configurations.
 */
final class InvalidGuidesXmlTest extends TestCase
{
    private const FIXTURES_PATH = __DIR__ . '/tests/invalid-guides-xml/input';

    public function testInvalidGuidesXmlShowsHelpfulErrorMessage(): void
    {
        $binPath = dirname(__DIR__, 2) . '/bin/guides';

        $process = new Process([
            'php',
            $binPath,
            'run',
            '--config=' . self::FIXTURES_PATH,
            self::FIXTURES_PATH,
        ]);

        $process->run();

        // Should fail with exit code 1, not crash with fatal error
        self::assertSame(1, $process->getExitCode(), 'Expected exit code 1 for invalid guides.xml');

        $stderr = $process->getErrorOutput();

        // Should contain helpful error message, not PHP fatal error
        self::assertStringContainsString('Invalid guides.xml configuration', $stderr);
        self::assertStringNotContainsString('PHP Fatal error', $stderr);
        self::assertStringNotContainsString('Stack trace', $stderr);

        // Should reference documentation
        self::assertStringContainsString('https://docs.typo3.org', $stderr);
    }

    public function testInvalidGuidesXmlShowsCommonCauses(): void
    {
        $binPath = dirname(__DIR__, 2) . '/bin/guides';

        $process = new Process([
            'php',
            $binPath,
            'run',
            '--config=' . self::FIXTURES_PATH,
            self::FIXTURES_PATH,
        ]);

        $process->run();

        $stderr = $process->getErrorOutput();

        // Should show common causes section
        self::assertStringContainsString('Common causes', $stderr);
    }

    public function testValidGuidesXmlRendersSuccessfully(): void
    {
        $binPath = dirname(__DIR__, 2) . '/bin/guides';
        $validFixturePath = __DIR__ . '/tests/getting-started/input';

        // Skip if fixture doesn't exist
        if (!is_dir($validFixturePath)) {
            self::markTestSkipped('Valid fixture not available');
        }

        $outputPath = sys_get_temp_dir() . '/render-guides-test-' . uniqid();

        $process = new Process([
            'php',
            $binPath,
            'run',
            '--config=' . $validFixturePath,
            '--output=' . $outputPath,
            $validFixturePath,
        ]);

        $process->run();

        // Clean up
        if (is_dir($outputPath)) {
            system('rm -rf ' . escapeshellarg($outputPath));
        }

        // Should succeed
        self::assertSame(0, $process->getExitCode(), 'Expected exit code 0 for valid guides.xml. Error: ' . $process->getErrorOutput());
    }
}
