<?php

declare(strict_types=1);

namespace T3Docs\GuidesCli\Migration;

use T3Docs\GuidesCli\Migration\Dto\ProcessingResult;
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

    public function process(string $inputFile, string $outputFile): ProcessingResult
    {
        $legacySettings = $this->legacySettingsRepository->get($inputFile);
        $migrationResult = $this->settingsMigrator->migrate($legacySettings);

        $fp = @fopen($outputFile, 'w') ?: throw ProcessingException::fileCannotBeCreated($outputFile);
        $xmlString = $migrationResult->xmlDocument->saveXML() ?: throw ProcessingException::xmlCannotBeGenerated();
        fwrite($fp, $xmlString);
        fclose($fp);

        return new ProcessingResult($migrationResult->numberOfConvertedSettings, $migrationResult->messages);
    }
}
