<?php

declare(strict_types=1);

namespace MyVendor\MyExtension\Enum;

/**
 * Whether a thing is shown in the frontend.
 */
enum ThingState: string
{
    case Visible = 'visible';
    /** Not shown, but kept. */
    case Hidden = 'hidden';

    public function isShown(): bool
    {
        return $this === self::Visible;
    }
}
