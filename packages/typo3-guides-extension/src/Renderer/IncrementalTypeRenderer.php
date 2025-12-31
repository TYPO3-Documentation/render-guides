<?php

declare(strict_types=1);

namespace T3Docs\GuidesExtension\Renderer;

use League\Tactician\CommandBus;
use phpDocumentor\Guides\Handlers\RenderCommand;
use phpDocumentor\Guides\Handlers\RenderDocumentCommand;
use phpDocumentor\Guides\RenderContext;
use phpDocumentor\Guides\Renderer\TypeRenderer;
use Psr\Log\LoggerInterface;
use T3Docs\GuidesExtension\Compiler\Cache\IncrementalBuildCache;
use T3Docs\GuidesExtension\EventListener\IncrementalCacheListener;

/**
 * Incremental type renderer that skips unchanged documents.
 *
 * Wraps the standard rendering process but checks the dirty set
 * to skip documents that haven't changed and don't need re-rendering.
 */
final class IncrementalTypeRenderer implements TypeRenderer
{
    /** @var array<string, int> Documents that need rendering (values are indexes) */
    private array $dirtySet = [];

    private bool $incrementalEnabled = false;

    private int $skippedCount = 0;
    private int $renderedCount = 0;

    public function __construct(
        private readonly CommandBus $commandBus,
        private readonly IncrementalBuildCache $cache,
        private readonly IncrementalCacheListener $cacheListener,
        private readonly ?LoggerInterface $logger = null,
    ) {}

    public function render(RenderCommand $renderCommand): void
    {

        $context = RenderContext::forProject(
            $renderCommand->getProjectNode(),
            $renderCommand->getDocumentArray(),
            $renderCommand->getOrigin(),
            $renderCommand->getDestination(),
            $renderCommand->getDestinationPath(),
            $renderCommand->getOutputFormat(),
        )->withIterator($renderCommand->getDocumentIterator());

        // Compute dirty set if incremental is enabled
        $this->computeDirtySet($renderCommand);

        $this->skippedCount = 0;
        $this->renderedCount = 0;

        foreach ($context->getIterator() as $document) {
            $filePath = $document->getFilePath();

            // Check if we can skip this document
            if ($this->canSkipDocument($filePath)) {
                $this->skippedCount++;
                $this->logger?->debug('Skipping unchanged document: ' . $filePath);
                continue;
            }

            $this->renderedCount++;
            $this->commandBus->handle(
                new RenderDocumentCommand(
                    $document,
                    $context->withDocument($document),
                ),
            );

            // Store output path for future reference
            $this->cache->setOutputPath($filePath, $renderCommand->getDestinationPath() . '/' . $filePath);
        }

        if ($this->incrementalEnabled && ($this->skippedCount > 0 || $this->renderedCount > 0)) {
            $this->logger?->info(sprintf(
                'Incremental render: %d rendered, %d skipped (%.1f%% saved)',
                $this->renderedCount,
                $this->skippedCount,
                $this->skippedCount > 0 ? ($this->skippedCount / ($this->renderedCount + $this->skippedCount)) * 100 : 0
            ));
        }
    }

    /**
     * Compute the dirty set based on change detection and dependency propagation.
     */
    private function computeDirtySet(RenderCommand $command): void
    {
        // Check if incremental is enabled via the cache listener
        if (!$this->cacheListener->isIncrementalEnabled()) {
            $this->incrementalEnabled = false;
            $this->logger?->debug('Incremental rendering disabled - full render required');
            return;
        }

        // Get the dirty set from the listener
        $allDirty = $this->cacheListener->computeDirtySet();

        // Build dirty set lookup - empty means no documents changed (skip all)
        $this->dirtySet = array_flip($allDirty);
        $this->incrementalEnabled = true;

        $this->logger?->debug(sprintf('Incremental rendering enabled - %d documents marked dirty', count($allDirty)));
    }

    /**
     * Check if a document can be skipped (is clean).
     */
    private function canSkipDocument(string $filePath): bool
    {
        if (!$this->incrementalEnabled) {
            return false;
        }

        // Document is dirty if it's in the dirty set
        return !isset($this->dirtySet[$filePath]);
    }

    /**
     * Enable/disable incremental rendering.
     */
    public function setIncrementalEnabled(bool $enabled): void
    {
        $this->incrementalEnabled = $enabled;
    }

    /**
     * Set the dirty set directly (for testing or external control).
     *
     * @param string[] $dirtyDocuments
     */
    public function setDirtySet(array $dirtyDocuments): void
    {
        $this->dirtySet = array_flip($dirtyDocuments);
        $this->incrementalEnabled = true;
    }

    /**
     * Get the number of skipped documents in the last render.
     */
    public function getSkippedCount(): int
    {
        return $this->skippedCount;
    }

    /**
     * Get the number of rendered documents in the last render.
     */
    public function getRenderedCount(): int
    {
        return $this->renderedCount;
    }
}
