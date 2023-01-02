<?php

declare(strict_types=1);

namespace T3Docs\Theme\TextRoles;

use Doctrine\RST\TextRoles\TextRole;

class KbdRole extends TextRole
{
    public function getName(): string
    {
        return 'kbd';
    }

    public function process(string $text): string
    {
        return '<kbd class="kbd docutils literal notranslate">' . $text . '</kbd>';
    }
}
