<?php

declare(strict_types=1);

namespace T3Docs\GuidesCli\Repository;

use T3Docs\GuidesCli\Repository\Exception\FileException;

class LegacySettingsRepository
{
    /**
     * @return array<string, string>
     */
    public function get(string $filePath): array
    {
        if (!file_exists($filePath)) {
            throw FileException::notFound($filePath);
        }

        // Settings.cfg can be parsed as an INI file. If it fails, bail out.
        $fileContent = file_get_contents($filePath);
        if ($fileContent === false) {
            throw FileException::notReadable($filePath);
        }

        // Remove lines starting with a hashtag and optional whitespace
        $filteredContent = preg_replace('/^\s*#.*$/m', '', $fileContent);
        if ($filteredContent === null) {
            throw FileException::notParsable($filePath);
        }

        // When invalid syntax is given an error is thrown. We do not want this,
        // as we check the result of the function, so we silence the error with
        // "@".
        $settings = @parse_ini_string($filteredContent, true, INI_SCANNER_RAW);
        if (!is_array($settings)) {
            throw FileException::notParsable($filePath);
        }

        return $settings;
    }
}
