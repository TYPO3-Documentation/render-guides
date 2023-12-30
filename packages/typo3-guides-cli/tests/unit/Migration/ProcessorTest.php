<?php

declare(strict_types=1);

namespace T3Docs\GuidesCli\Tests\Migration;

use PHPUnit\Framework\Attributes\Test;
use T3Docs\GuidesCli\Migration\Dto\MigrationResult;
use T3Docs\GuidesCli\Migration\Exception\ProcessingException;
use T3Docs\GuidesCli\Migration\Processor;
use PHPUnit\Framework\TestCase;
use T3Docs\GuidesCli\Migration\SettingsMigrator;
use T3Docs\GuidesCli\Repository\LegacySettingsRepository;

final class ProcessorTest extends TestCase
{
    private Processor $subject;

    protected function setUp(): void
    {
        $legacySettingsRepositoryStub = self::createStub(LegacySettingsRepository::class);
        $legacySettingsRepositoryStub
            ->method('get')
            ->with('/path/to/Settings.cfg')
            ->willReturn(['section' => ['key' => 'value']]);

        $xmlDocument = new \DOMDocument('1.0', 'UTF-8');
        $xmlDocument->appendChild($xmlDocument->createElement('guidesForTesting'));
        $settingsMigratorStub = self::createStub(SettingsMigrator::class);
        $settingsMigratorStub
            ->method('migrate')
            ->with(['section' => ['key' => 'value']])
            ->willReturn(
                new MigrationResult(
                    $xmlDocument,
                    42,
                    [
                        'some message',
                        'another message',
                    ],
                )
            );

        $this->subject = new Processor($legacySettingsRepositoryStub, $settingsMigratorStub);
    }

    #[Test]
    public function xmlFileIsWrittenCorrectly(): void
    {
        $outputFile = tempnam(sys_get_temp_dir(), 'guides-cli-');
        $actual = $this->subject->process('/path/to/Settings.cfg', $outputFile);

        self::assertXmlStringEqualsXmlString('<?xml version="1.0"?><guidesForTesting/>', file_get_contents($outputFile));
        self::assertSame(42, $actual->numberOfConvertedSettings);
        self::assertSame(['some message', 'another message'], $actual->migrationMessages);
    }

    #[Test]
    public function exceptionIsThrownWhenFileCannotBeCreated(): void
    {
        $this->expectException(ProcessingException::class);
        $this->expectExceptionMessage('Could not create file "/tmp/non-existing/guides-cli.tmp"');

        $outputFile = sys_get_temp_dir() . '/non-existing/guides-cli.tmp';

        $this->subject->process('/path/to/Settings.cfg', $outputFile);
    }
}
