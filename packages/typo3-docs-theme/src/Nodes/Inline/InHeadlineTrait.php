<?php

declare(strict_types=1);

namespace T3Docs\Typo3DocsTheme\Nodes\Inline;

/** @see InHeadline */
trait InHeadlineTrait
{
    private bool $inHeadline = false;

    public function markInHeadline(): void
    {
        $this->inHeadline = true;
    }

    public function isInHeadline(): bool
    {
        return $this->inHeadline;
    }
}
