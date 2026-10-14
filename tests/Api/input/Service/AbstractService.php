<?php

declare(strict_types=1);

namespace MyVendor\MyExtension\Service;

abstract class AbstractService
{
    public const VERSION = 2;

    /** @var string[] */
    protected array $log = [];

    /**
     * Returns what the service did, newest last.
     *
     * @return string[]
     */
    public function getLog(): array
    {
        return $this->log;
    }

    abstract protected function persist(string $title): int;
}
