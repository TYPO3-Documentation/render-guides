<?php

declare(strict_types=1);

namespace MyVendor\MyExtension\Event;

/**
 * Dispatched after a thing is deleted.
 */
final readonly class AfterThingDeletedEvent
{
    public function __construct(public int $uid) {}
}
