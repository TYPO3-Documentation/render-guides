<?php

declare(strict_types=1);

namespace T3Docs\GuidesCli\Migration\Dto;

final readonly class MigrationResult
{
    /**
     * @param list<string> $messages
     */
    public function __construct(
        public \DOMDocument $xmlDocument,
        public int $numberOfConvertedSettings,
        public array $messages,
    ) {}
}
