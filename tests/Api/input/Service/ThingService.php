<?php

declare(strict_types=1);

namespace MyVendor\MyExtension\Service;

use MyVendor\MyExtension\Enum\ThingState;
use MyVendor\MyExtension\Event\AfterThingSavedEvent;
use MyVendor\MyExtension\Event\BeforeThingSavedEvent;
use MyVendor\MyExtension\Message\ThingSavedMessage;
use Psr\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Messenger\MessageBusInterface;

/**
 * Saves things and tells listeners about it.
 */
final class ThingService extends AbstractService implements ThingServiceInterface
{
    use LoggingTrait;

    public function __construct(
        private readonly EventDispatcherInterface $eventDispatcher,
        private readonly MessageBusInterface $bus,
    ) {}

    public function save(string $title): int
    {
        // An event created in the call
        $title = $this->eventDispatcher->dispatch(new BeforeThingSavedEvent($title))->getTitle();
        try {
            $uid = $this->persist($title);
            // An event assigned to a variable first, inside a block
            $event = new AfterThingSavedEvent($uid, ThingState::Visible);
            $this->eventDispatcher->dispatch($event);
        } finally {
            // A message, not an event
            $this->bus->dispatch(new ThingSavedMessage($title));
        }

        return $uid;
    }

    /**
     * Tells the listeners again about a thing that was saved before.
     */
    public function notify(AfterThingSavedEvent $event): AfterThingSavedEvent
    {
        return $this->eventDispatcher->dispatch($event);
    }

    /**
     * @deprecated since 2.0, use save() instead.
     */
    public function store(string $title): int
    {
        return $this->save($title);
    }

    /**
     * @internal Only for the tests of this extension.
     */
    public function reset(): static
    {
        $this->log = [];
        return $this;
    }

    protected function persist(string $title, int $pid = self::VERSION, ?string &$error = null, string ...$tags): int
    {
        $this->log[] = $title;
        return count($this->log);
    }
}
