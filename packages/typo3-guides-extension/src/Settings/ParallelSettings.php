<?php

declare(strict_types=1);

namespace T3Docs\GuidesExtension\Settings;

/**
 * Singleton service to store parallel rendering configuration.
 *
 * This allows the CLI --parallel-workers option to be propagated
 * to all parallel rendering components (ForkingRenderer, ParallelCompiler, etc.)
 */
final class ParallelSettings
{
    /**
     * Number of worker processes.
     * -1 = disabled (truly sequential)
     *  0 = auto-detect CPU cores
     *  N = explicit worker count
     */
    private int $workerCount = 0;

    /** Whether parallel processing is enabled */
    private bool $enabled = true;

    public function setWorkerCount(int $count): void
    {
        $this->workerCount = $count;

        // -1 means disabled
        if ($count === -1) {
            $this->enabled = false;
            $this->workerCount = 1;
        } else {
            $this->enabled = true;
        }
    }

    public function getWorkerCount(): int
    {
        return $this->workerCount;
    }

    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    /**
     * Get effective worker count for forking.
     * Returns 0 for auto-detection, or the explicit count.
     */
    public function getEffectiveWorkerCount(): int
    {
        if (!$this->enabled) {
            return 1; // Sequential
        }

        return $this->workerCount;
    }

    /**
     * Resolve the actual number of workers to use.
     *
     * @param int $autoDetectedCount The auto-detected CPU count
     * @return int The number of workers to use
     */
    public function resolveWorkerCount(int $autoDetectedCount): int
    {
        if (!$this->enabled) {
            return 1;
        }

        if ($this->workerCount === 0) {
            return $autoDetectedCount; // Auto-detect
        }

        return min($this->workerCount, 16); // Cap at 16
    }
}
