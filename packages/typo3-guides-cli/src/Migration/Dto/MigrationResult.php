<?php

declare(strict_types=1);

namespace T3Docs\GuidesCli\Migration\Dto;

final class MigrationResult
{
    /**
     * @param list<string> $messages
     */
    public function __construct(
        public readonly \DOMDocument $xmlDocument,
        public readonly int $numberOfConvertedSettings,
        public readonly array $messages,
    ) {}
}
