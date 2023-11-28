<?php

declare(strict_types=1);

namespace T3Docs\Typo3DocsTheme\Integration;

use phpDocumentor\Guides\Cli\Command\Run;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\ExpectationFailedException;
use RuntimeException;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Finder\Finder as SymfonyFinder;
use T3Docs\GuidesExtension\Command\RunDecorator;
use T3Docs\Typo3DocsTheme\ApplicationTestCase;

use function array_filter;
use function array_merge;
use function array_walk;
use function assert;
use function escapeshellarg;
use function explode;
use function file_exists;
use function file_get_contents;
use function implode;
use function setlocale;
use function str_ends_with;
use function str_replace;
use function system;
use function trim;

use const LC_ALL;

final class IntegrationTest extends ApplicationTestCase
{
    private const CONTENT_START = '<!-- content start -->';
    private const CONTENT_END = '<!-- content end -->';

    /** @param list<string> $compareFiles */
    private function compareHtmlIntegration(string $outputPath, string $inputPath, string $expectedPath, array $compareFiles, bool $htmlOnlyBetweenMarkers): void
    {
        system('rm -rf ' . escapeshellarg($outputPath));
        self::assertDirectoryExists($inputPath);
        self::assertDirectoryExists($expectedPath);
        self::assertNotEmpty($compareFiles);

        $isIncomplete = file_exists($inputPath . '/incomplete');
        $isSkipped = file_exists($inputPath . '/skip');
        if ($isSkipped) {
            self::markTestSkipped(file_get_contents($inputPath . '/skip') ?: '');
        }

        $configurationFile = null;
        if (file_exists($inputPath . '/guides.xml')) {
            $configurationFile = $inputPath . '/guides.xml';
        }

        try {
            system('mkdir ' . escapeshellarg($outputPath));

            $this->prepareContainer($configurationFile);
            $command = $this->getContainer()->get(Run::class);
            assert($command instanceof Run || $command instanceof RunDecorator);

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
                } elseif ($htmlOnlyBetweenMarkers && str_ends_with($compareFile, '.html')) {
                    self::assertFileEqualsTrimmedBetweenMarkers(
                        $compareFile,
                        $outputFile,
                        'Expected file path: ' . $compareFile,
                        self::CONTENT_START,
                        self::CONTENT_END
                    );
                } else {
                    self::assertFileEqualsTrimmed($compareFile, $outputFile, 'Expected file path: ' . $compareFile);
                }
            }
        } catch (ExpectationFailedException $e) {
            if ($isIncomplete) {
                self::markTestIncomplete(file_get_contents($inputPath . '/incomplete') ?: '');
            }

            throw $e;
        }

        self::assertFalse($isIncomplete, 'Test passes while marked as incomplete.');
    }

    protected function setUp(): void
    {
        setlocale(LC_ALL, 'en_US.utf8');
    }

    /** @param list<string> $compareFiles */
    #[DataProvider('getTestsForDirectoryTest')]
    public function testHtmlIntegrationBetweenMarkers(
        string $inputPath,
        string $expectedPath,
        string $outputPath,
        array $compareFiles,
    ): void {
        $this->compareHtmlIntegration($outputPath, $inputPath, $expectedPath, $compareFiles, true);
    }

    /** @param list<string> $compareFiles */
    #[DataProvider('getTestsForDirectoryTestsFull')]
    public function testHtmlIntegrationFullFile(
        string $inputPath,
        string $expectedPath,
        string $outputPath,
        array $compareFiles,
    ): void {
        $this->compareHtmlIntegration($outputPath, $inputPath, $expectedPath, $compareFiles, false);
    }

    /**
     * Asserts that each line of the expected file is contained in actual
     *
     * @throws ExpectationFailedException
     */
    private static function assertFileEqualsTrimmedBetweenMarkers(string $expected, string $actual, string $message, string $startMarker, string $endMarker): void
    {
        self::assertFileExists($expected);
        self::assertFileExists($actual);

        $expectedContent = self::extractContentBetweenMarkers($expected, $startMarker, $endMarker);
        $actualContent = self::extractContentBetweenMarkers($actual, $startMarker, $endMarker);

        self::assertEquals(self::getTrimmedFileContent($expectedContent), self::getTrimmedFileContent($actualContent), $message);
    }

    /**
     * Extract content between specified markers in a file.
     */
    private static function extractContentBetweenMarkers(string $filePath, string $startMarker, string $endMarker): string
    {
        $fileContent = file_get_contents($filePath);
        $startPos = strpos($fileContent, $startMarker);
        $endPos = strpos($fileContent, $endMarker, $startPos + strlen($startMarker));

        if ($startPos === false || $endPos === false) {
            throw new RuntimeException('Start or end marker not found in file: ' . $filePath);
        }

        return substr($fileContent, $startPos + strlen($startMarker), $endPos - $startPos - strlen($startMarker));
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

        self::assertEquals(self::getTrimmedFileContent(file_get_contents($expected)), self::getTrimmedFileContent(file_get_contents($actual)), $message);
    }

    private static function getTrimmedFileContent(string $content): string
    {
        $contentArray = explode("\n", $content);
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
    public static function getTestsForDirectoryTestsFull(): array
    {
        return self::getTestsForDirectory(__DIR__ . '/tests-full');
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
