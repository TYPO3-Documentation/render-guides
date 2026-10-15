<?php

declare(strict_types=1);

namespace MyVendor\MyExtension\Service;

interface ThingServiceInterface
{
    /** The table that stores things. */
    public const TABLE = 'tx_myextension_thing';

    /**
     * @return int the uid of the saved thing
     */
    public function save(string $title): int;
}
