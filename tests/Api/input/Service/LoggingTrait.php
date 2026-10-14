<?php

declare(strict_types=1);

namespace MyVendor\MyExtension\Service;

trait LoggingTrait
{
    public function clearLog(): void
    {
        $this->log = [];
    }
}
