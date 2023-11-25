<?php

declare(strict_types=1);

namespace T3Docs\GuidesCli\Repository;

use T3Docs\GuidesCli\Repository\Exception\FileException;

class GuidesSettingsRepository
{
    /**
     * @return int Number of bytes written
     */
    public function create(string $filePath, \DomDocument $document): int
    {
        // An error is suppressed to avoid PHP warnings when the file does
        // not exist. As we check the return value later this should be okay.
        $result = @file_put_contents($filePath, $document->saveXml());

        return $result ?: throw FileException::notWritable($filePath);
    }
}
