<?php

declare(strict_types=1);

namespace T3Docs\Typo3Api\Events\Reflection;

use phpDocumentor\Reflection\Fqsen;
use phpDocumentor\Reflection\Metadata\Metadata;

/**
 * The methods and functions that dispatch an event, on the page of the event.
 */
final class DispatchedBy implements Metadata
{
    public const KEY = 'typo3_dispatched_by';

    /** @param list<Fqsen> $dispatchers */
    public function __construct(private readonly array $dispatchers) {}

    public function key(): string
    {
        return self::KEY;
    }

    /** @return list<Fqsen> */
    public function getDispatchers(): array
    {
        return $this->dispatchers;
    }
}
