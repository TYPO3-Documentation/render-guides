<?php

declare(strict_types=1);

namespace T3Docs\GuidesExtension\Compiler\Cache;

/**
 * Result of dirty propagation through the dependency graph.
 */
final class PropagationResult
{
    /**
     * @param string[] $documentsToRender Documents that need to be re-rendered
     * @param string[] $documentsToSkip Documents that can use cached output
     * @param string[] $propagatedFrom Documents that caused additional invalidations
     */
    public function __construct(
        public readonly array $documentsToRender,
        public readonly array $documentsToSkip,
        public readonly array $propagatedFrom = [],
    ) {}

    /**
     * Check if a document needs rendering.
     */
    public function needsRendering(string $docPath): bool
    {
        return in_array($docPath, $this->documentsToRender, true);
    }

    /**
     * Get count of documents to render.
     */
    public function getRenderCount(): int
    {
        return count($this->documentsToRender);
    }

    /**
     * Get count of documents to skip.
     */
    public function getSkipCount(): int
    {
        return count($this->documentsToSkip);
    }

    /**
     * Get savings ratio (0.0 - 1.0).
     */
    public function getSavingsRatio(): float
    {
        $total = $this->getRenderCount() + $this->getSkipCount();
        if ($total === 0) {
            return 0.0;
        }
        return $this->getSkipCount() / $total;
    }

    /**
     * Serialize to array.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'documentsToRender' => $this->documentsToRender,
            'documentsToSkip' => $this->documentsToSkip,
            'propagatedFrom' => $this->propagatedFrom,
        ];
    }
}
