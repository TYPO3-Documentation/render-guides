<?php

declare(strict_types=1);

namespace T3Docs\Typo3DocsTheme\Integration;

use function array_filter;
use function array_merge;
use function array_walk;
use function assert;
use function escapeshellarg;
use function explode;
use function file_exists;
use function file_get_contents;
use function implode;

use const LC_ALL;

use phpDocumentor\Guides\Cli\Command\Run;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\ExpectationFailedException;

use function setlocale;
use function str_ends_with;
use function str_replace;

use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Finder\Finder as SymfonyFinder;

use function system;

use T3Docs\Typo3DocsTheme\ApplicationTestCase;

use function trim;

final class IntegrationTest extends ApplicationTestCase
{
    protected function setUp(): void
    {
        setlocale(LC_ALL, 'en_US.utf8');
    }

    /** @param list<string> $compareFiles */
    #[DataProvider('getTestsForDirectoryTest')]
    public function testHtmlIntegration(
        string $inputPath,
        string $expectedPath,
        string $outputPath,
        array $compareFiles,
    ): void {
        system('rm -rf ' . escapeshellarg($outputPath));
        self::assertDirectoryExists($inputPath);
        self::assertDirectoryExists($expectedPath);
        self::assertNotEmpty($compareFiles);

        $skip = file_exists($inputPath . '/skip');
        $configurationFile = null;
        if (file_exists($inputPath . '/guides.xml')) {
            $configurationFile = $inputPath . '/guides.xml';
        }

        try {
            system('mkdir ' . escapeshellarg($outputPath));

            $this->prepareContainer($configurationFile);
            $command = $this->getContainer()->get(Run::class);
            assert($command instanceof Run);

            $input = new ArrayInput(
                [
                    'input' => $inputPath,
                    '--output' => $outputPath,
                    '--theme' => 'typo3docs',
                    '--log-path' => $outputPath . '/logs',
                ],
                $command->getDefinition(),
            );

            $outputBuffer = new BufferedOutput();

            $command->run(
                $input,
                $outputBuffer,
            );
            if (!file_exists($expectedPath . '/logs/error.log')) {
                self::assertFileDoesNotExist($outputPath . '/logs/error.log');
            }

            if (!file_exists($expectedPath . '/logs/warning.log')) {
                self::assertFileDoesNotExist($outputPath . '/logs/warning.log');
            }

            foreach ($compareFiles as $compareFile) {
                $outputFile = str_replace($expectedPath, $outputPath, $compareFile);
                if (str_ends_with($compareFile, '.log')) {
                    self::assertFileContainsLines($compareFile, $outputFile);
                } else {
                    self::assertFileEqualsTrimmed($compareFile, $outputFile, 'Expected file path: ' . $compareFile);
                }
            }
        } catch (ExpectationFailedException $e) {
            if ($skip) {
                self::markTestIncomplete(file_get_contents($inputPath . '/skip') ?: '');
            }

            throw $e;
        }

        self::assertFalse($skip, 'Test passes while marked as SKIP.');
    }

    /**
     * Asserts that each line of the expected file is contained in actual
     *
     * @throws ExpectationFailedException
     */
    private static function assertFileContainsLines(string $expected, string $actual): void
    {
        self::assertFileExists($expected);
        self::assertFileExists($actual);

        $lines = explode("\n", file_get_contents($expected));
        $actualContent =  file_get_contents($actual);
        foreach ($lines as $line) {
            self::assertStringContainsString($line, $actualContent, 'File "' . $actual . '" does not contain "' . $line . '"');
        }
    }

    /**
     * Asserts that the contents of one file is equal to the contents of another
     * file. It ignores empty lines and whitespace at the start and end of each line
     *
     * @throws ExpectationFailedException
     */
    private static function assertFileEqualsTrimmed(string $expected, string $actual, string $message = ''): void
    {
        self::assertFileExists($expected, $message);
        self::assertFileExists($actual, $message);

        self::assertEquals(self::getTrimmedFileContent($expected), self::getTrimmedFileContent($actual), $message);
    }

    private static function getTrimmedFileContent(string $file): string
    {
        $contentArray = explode("\n", file_get_contents($file));
        array_walk($contentArray, static function (&$value): void {
            $value = trim($value);
        });
        $contentArray = array_filter($contentArray, static function ($value) {
            return $value !== '';
        });

        return implode("\n", $contentArray);
    }

    /** @return array{string, string, string, list<string>} */
    public static function getTestsForDirectoryTest(): array
    {
        return self::getTestsForDirectory(__DIR__ . '/tests');
    }

    /** @return array{string, string, string, list<string>} */
    private static function getTestsForDirectory(string $directory): array
    {
        $finder = new SymfonyFinder();
        $finder
            ->directories()
            ->in($directory)
            ->depth('== 0');

        $tests = [];

        foreach ($finder as $dir) {
            if (!file_exists($dir->getPathname() . '/input')) {
                $tests = array_merge($tests, self::getTestsForDirectory($dir->getPathname()));
                continue;
            }

            $compareFiles = [];
            $fileFinder = new SymfonyFinder();
            $fileFinder
                ->files()
                ->in($dir->getPathname() . '/expected');
            foreach ($fileFinder as $file) {
                $compareFiles[] = $file->getPathname();
            }

            $tests[$dir->getRelativePathname()] = [
                $dir->getPathname() . '/input',
                $dir->getPathname() . '/expected',
                $dir->getPathname() . '/temp',
                $compareFiles,
            ];
        }

        return $tests;
    }
}
