<?php

declare(strict_types=1);

namespace T3Docs\GuidesExtension\Compiler\Cache;

/**
 * Represents the exported symbols (anchors, titles, citations) from a single document.
 * Used for incremental rendering to detect when a document's "public interface" changes.
 */
final class DocumentExports
{
    /**
     * @param string $documentPath Source file path (relative)
     * @param string $contentHash Hash of the source file content
     * @param string $exportsHash Hash of exports only (for dependency invalidation)
     * @param array<string, string> $anchors Anchor name => title mapping
     * @param array<string, string> $sectionTitles Section ID => title mapping
     * @param array<string> $citations Citation names defined in this document
     * @param int $lastModified Unix timestamp of last modification
     */
    public function __construct(
        public readonly string $documentPath,
        public readonly string $contentHash,
        public readonly string $exportsHash,
        public readonly array $anchors,
        public readonly array $sectionTitles,
        public readonly array $citations,
        public readonly int $lastModified,
    ) {}

    /**
     * Check if the exports (public interface) changed compared to another version.
     * Content can change without exports changing (e.g., fixing a typo in body text).
     */
    public function hasExportsChanged(self $other): bool
    {
        return $this->exportsHash !== $other->exportsHash;
    }

    /**
     * Check if any content changed.
     */
    public function hasContentChanged(self $other): bool
    {
        return $this->contentHash !== $other->contentHash;
    }

    /**
     * Get all anchor names exported by this document.
     *
     * @return string[]
     */
    public function getAnchorNames(): array
    {
        return array_keys($this->anchors);
    }

    /**
     * Serialize to array for JSON persistence.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'documentPath' => $this->documentPath,
            'contentHash' => $this->contentHash,
            'exportsHash' => $this->exportsHash,
            'anchors' => $this->anchors,
            'sectionTitles' => $this->sectionTitles,
            'citations' => $this->citations,
            'lastModified' => $this->lastModified,
        ];
    }

    /**
     * Deserialize from array.
     *
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        /** @var array<string, string> $anchors */
        $anchors = $data['anchors'] ?? [];
        /** @var array<string, string> $sectionTitles */
        $sectionTitles = $data['sectionTitles'] ?? [];
        /** @var array<string> $citations */
        $citations = $data['citations'] ?? [];

        return new self(
            documentPath: (string) ($data['documentPath'] ?? ''),
            contentHash: (string) ($data['contentHash'] ?? ''),
            exportsHash: (string) ($data['exportsHash'] ?? ''),
            anchors: $anchors,
            sectionTitles: $sectionTitles,
            citations: $citations,
            lastModified: (int) ($data['lastModified'] ?? 0),
        );
    }
}
