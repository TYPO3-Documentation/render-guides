<?php

declare(strict_types=1);

namespace T3Docs\Theme;

use Doctrine\RST\Kernel;
use Doctrine\RST\TextRoles\TextRole;
use T3Docs\Theme\TextRoles\KbdRole;

class T3Kernel extends Kernel
{
    /** @return TextRole[] */
    protected function createTextRoles(): array
    {
        return [new KbdRole()];
    }
}
