<?php

declare(strict_types=1);

namespace T3Docs\GuidesCli\Migration\Dto;

final class ProcessingResult
{
    /**
     * @param list<string> $migrationMessages
     */
    public function __construct(
        public readonly int $numberOfConvertedSettings,
        public readonly array $migrationMessages,
    ) {}
}
