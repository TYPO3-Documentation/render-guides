<?php

declare(strict_types=1);

namespace T3Docs\GuidesCli\Migration;

use T3Docs\GuidesCli\Migration\Exception\ProcessingException;
use T3Docs\GuidesCli\Repository\LegacySettingsRepository;

class Processor
{
    private readonly LegacySettingsRepository $legacySettingsRepository;
    private readonly SettingsMigrator $settingsMigrator;

    /**
     * Arguments for testing only!
     */
    public function __construct(
        ?LegacySettingsRepository $legacySettingsRepository = null,
        ?SettingsMigrator $settingsMigrator = null,
    ) {
        $this->legacySettingsRepository = $legacySettingsRepository ?? new LegacySettingsRepository();
        $this->settingsMigrator = $settingsMigrator ?? new SettingsMigrator();
    }

    /**
     * @return array{0: int, 1: list<string>}
     */
    public function process(string $inputFile, string $outputFile): array
    {
        $legacySettings = $this->legacySettingsRepository->get($inputFile);
        [$xmlDocument, $convertedSettings, $migrationMessages]
            = $this->settingsMigrator->migrate($legacySettings);

        $fp = @fopen($outputFile, 'w') ?: throw ProcessingException::fileCannotBeCreated($outputFile);
        $xmlString = $xmlDocument->saveXML() ?: throw ProcessingException::xmlCannotBeGenerated();
        fwrite($fp, $xmlString);
        fclose($fp);

        return [$convertedSettings, $migrationMessages];
    }
}
