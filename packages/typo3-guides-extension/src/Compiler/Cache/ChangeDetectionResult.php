<?php

declare(strict_types=1);

namespace T3Docs\GuidesExtension\Compiler\Cache;

/**
 * Result of comparing current source files against cached state.
 */
final class ChangeDetectionResult
{
    /**
     * @param string[] $dirty Files that changed since last build
     * @param string[] $clean Files unchanged since last build
     * @param string[] $new Files not seen before
     * @param string[] $deleted Files that existed before but are now gone
     */
    public function __construct(
        public readonly array $dirty,
        public readonly array $clean,
        public readonly array $new,
        public readonly array $deleted,
    ) {}

    /**
     * Get all files that need processing (dirty + new).
     *
     * @return string[]
     */
    public function getFilesToProcess(): array
    {
        return array_merge($this->dirty, $this->new);
    }

    /**
     * Check if any changes were detected.
     */
    public function hasChanges(): bool
    {
        return !empty($this->dirty) || !empty($this->new) || !empty($this->deleted);
    }

    /**
     * Get total count of changed items.
     */
    public function getChangeCount(): int
    {
        return count($this->dirty) + count($this->new) + count($this->deleted);
    }

    /**
     * Serialize to array.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'dirty' => $this->dirty,
            'clean' => $this->clean,
            'new' => $this->new,
            'deleted' => $this->deleted,
        ];
    }
}
