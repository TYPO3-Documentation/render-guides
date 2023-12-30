<?php

declare(strict_types=1);

namespace T3Docs\GuidesCli\Tests\Repository;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use T3Docs\GuidesCli\Repository\Exception\FileException;
use T3Docs\GuidesCli\Repository\LegacySettingsRepository;

final class LegacySettingsRepositoryTest extends TestCase
{
    private LegacySettingsRepository $subject;

    protected function setUp(): void
    {
        $this->subject = new LegacySettingsRepository();
    }

    #[Test]
    public function settingsAreReturnedCorrectly(): void
    {
        $actual = $this->subject->get(realpath(__DIR__ . '/../../fixtures/Settings.cfg') ?: '');

        self::assertSame([
            'general' => [
                'project' => 'Fluid ViewHelper Reference',
                'version' => 'main (development)',
                'release' => 'main (development)',
                'copyright' => 'since 2018 by the TYPO3 contributors',
            ],
            'html_theme_options' => [
                'project_home' => 'https://docs.typo3.org/other/typo3/view-helper-reference/main/en-us/',
                'project_contact' => 'https://typo3.slack.com/archives/C028JEPJL',
                'project_repository' => 'https://github.com/TYPO3-Documentation/TYPO3CMS-Reference-ViewHelper',
                'project_issues' => 'https://github.com/TYPO3-Documentation/TYPO3CMS-Reference-ViewHelper/issues',
                'project_discussions' => '',
                'use_opensearch' => '',
            ],
            'intersphinx_mapping' => [
                'h2document' => 'https://docs.typo3.org/m/typo3/docs-how-to-document/main/en-us/',
                't3coreapi' => 'https://docs.typo3.org/m/typo3/reference-coreapi/main/en-us/',
                'other_typo3fluid' => 'https://docs.typo3.org/other/typo3fluid/fluid/main/en-us/',
            ],
        ], $actual);
    }

    #[Test]
    public function whenFileDoesNotExistAnExceptionIsThrown(): void
    {
        $this->expectException(FileException::class);
        $this->expectExceptionMessageMatches('/not.*found/');

        $this->subject->get(sys_get_temp_dir() . '/cli_guides_test_' . uniqid() . '/Settings.cfg');
    }

    #[Test]
    public function whenFileCannotParsedAnExceptionIsThrown(): void
    {
        $this->expectException(FileException::class);
        $this->expectExceptionMessageMatches('/not.*parsed/');

        $folder = sys_get_temp_dir() . '/cli_guides_test_' . uniqid();
        $filePath = $folder . '/Settings.cfg';
        mkdir($folder);
        file_put_contents($filePath, '(invalid key) = some value');

        $this->subject->get($filePath);
    }
}
