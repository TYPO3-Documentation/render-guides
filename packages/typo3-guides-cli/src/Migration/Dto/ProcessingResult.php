<?php

declare(strict_types=1);

namespace T3Docs\GuidesCli\Migration\Dto;

final readonly class ProcessingResult
{
    /**
     * @param list<string> $migrationMessages
     */
    public function __construct(
        public int $numberOfConvertedSettings,
        public array $migrationMessages,
    ) {}
}
