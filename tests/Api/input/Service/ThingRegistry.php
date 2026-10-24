<?php

declare(strict_types=1);

namespace MyVendor\MyExtension\Service;

use MyVendor\MyExtension\Enum\ThingState;

/**
 * Keeps the states of things, by uid.
 *
 * phpDocumentor 3 cannot read an array key type such as ThingUid or
 * 0|positive-int. The JSON shows these types as they are written.
 *
 * @phpstan-type ThingUid 0|positive-int
 */
final class ThingRegistry
{
    /** @var array<ThingUid, ThingState> The states, by uid */
    public array $states = [];

    /** @var list<string> A type that phpDocumentor reads */
    public array $titles = [];

    /** @var array<string, array{label: string, icon: string}> A long default, which the page shortens */
    public array $actions = [
        'edit' => ['label' => 'LLL:EXT:my_extension/Resources/Private/Language/locallang.xlf:edit', 'icon' => 'actions-open'],
        'delete' => ['label' => 'LLL:EXT:my_extension/Resources/Private/Language/locallang.xlf:delete', 'icon' => 'actions-edit-delete'],
    ];

    /**
     * Replaces the states.
     *
     * @param array<0|positive-int, ThingState> $states The new states, by uid
     * @param bool $keep Whether to keep the states of the other things
     * @return array<ThingUid, ThingState> The states before
     */
    public function replace(array $states, bool $keep = false): array
    {
        $before = $this->states;
        $this->states = $keep ? $states + $this->states : $states;
        return $before;
    }

    /**
     * @return array<int, ThingState> A type that phpDocumentor reads
     */
    public function visible(): array
    {
        return array_filter($this->states, static fn(ThingState $state): bool => $state->isShown());
    }
}
