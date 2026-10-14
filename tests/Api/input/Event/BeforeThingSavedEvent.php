<?php

declare(strict_types=1);

namespace MyVendor\MyExtension\Event;

/**
 * Dispatched before a thing is saved. A listener can change the title.
 */
final class BeforeThingSavedEvent
{
    public function __construct(private string $title) {}

    public function getTitle(): string
    {
        return $this->title;
    }

    /**
     * @param string $title The new title, see {@see \MyVendor\MyExtension\Service\ThingService::save()}
     */
    public function setTitle(string $title): void
    {
        $this->title = $title;
    }
}
