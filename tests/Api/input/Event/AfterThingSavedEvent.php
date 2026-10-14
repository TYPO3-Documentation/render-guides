<?php

declare(strict_types=1);

namespace MyVendor\MyExtension\Event;

use MyVendor\MyExtension\Enum\ThingState;

/**
 * Dispatched after a thing is saved.
 */
final readonly class AfterThingSavedEvent
{
    public function __construct(
        public int $uid,
        public ThingState $state,
        /** @internal */
        public array $context = [],
    ) {}

    /**
     * Whether the thing is visible now.
     *
     * ```
     * if ($event->isVisible()) {
     *     $this->cache->flush();
     * }
     * ```
     */
    public function isVisible(): bool
    {
        return $this->state === ThingState::Visible;
    }
}
