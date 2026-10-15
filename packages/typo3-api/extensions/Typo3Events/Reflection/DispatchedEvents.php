<?php

declare(strict_types=1);

namespace T3Docs\Typo3Api\Events\Reflection;

use phpDocumentor\Reflection\Fqsen;
use phpDocumentor\Reflection\Metadata\Metadata;

final class DispatchedEvents implements Metadata
{
    public const KEY = 'typo3_dispatched_events';

    /** @var array<string, Fqsen> in the order of dispatch, indexed by the class name */
    private array $events = [];

    public function key(): string
    {
        return self::KEY;
    }

    public function add(Fqsen $event): void
    {
        $this->events[(string)$event] = $event;
    }

    /** @return list<Fqsen> */
    public function getEvents(): array
    {
        return array_values($this->events);
    }
}
