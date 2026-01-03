<?php

declare(strict_types=1);

namespace T3Docs\GuidesExtension\EventListener;

use phpDocumentor\Guides\Event\PostCollectFilesForParsingEvent;
use phpDocumentor\Guides\Event\PostParseProcess;
use phpDocumentor\Guides\Event\PostProjectNodeCreated;
use phpDocumentor\Guides\Event\PostRenderProcess;
use phpDocumentor\Guides\Event\PreRenderProcess;
use Psr\Log\LoggerInterface;

/**
 * Event listener for profiling the documentation rendering pipeline.
 *
 * Measures time spent in each phase:
 * - Parsing: PostProjectNodeCreated → PostParseProcess
 * - Compilation: PostParseProcess → PreRenderProcess
 * - Rendering: PreRenderProcess → PostRenderProcess
 *
 * Results are logged and can be output to a JSON file for analysis.
 */
final class ProfilingEventListener
{
    private float $startTime = 0.0;
    private float $postCollectTime = 0.0;
    private float $postParseTime = 0.0;
    private float $preRenderTime = 0.0;
    private float $postRenderTime = 0.0;

    private int $fileCount = 0;
    private int $documentCount = 0;

    private int $startMemory = 0;
    private int $postParseMemory = 0;
    private int $postRenderMemory = 0;

    private bool $enabled = false;

    /** @var float Accumulated rendering time across all formats */
    private float $totalRenderTime = 0.0;

    /** @var int Number of render passes */
    private int $renderPassCount = 0;

    public function __construct(
        private readonly ?LoggerInterface $logger = null,
    ) {
        // Enable profiling if GUIDES_PROFILING env var is set
        $this->enabled = (bool) getenv('GUIDES_PROFILING');
    }

    /**
     * Enable or disable profiling.
     */
    public function setEnabled(bool $enabled): void
    {
        $this->enabled = $enabled;
    }

    /**
     * Check if profiling is enabled.
     */
    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    /**
     * Called when project node is created - marks the start of the pipeline.
     */
    public function onPostProjectNodeCreated(PostProjectNodeCreated $event): void
    {
        if (!$this->enabled) {
            return;
        }

        $this->startTime = microtime(true);
        $this->startMemory = memory_get_usage(true);

        $this->logger?->debug('[Profiling] Pipeline started');
    }

    /**
     * Called after files are collected for parsing.
     */
    public function onPostCollectFilesForParsing(PostCollectFilesForParsingEvent $event): void
    {
        if (!$this->enabled) {
            return;
        }

        $this->postCollectTime = microtime(true);
        $files = $event->getFiles();
        $this->fileCount = iterator_count($files->getIterator());
        // Reset iterator after counting
        $files->getIterator()->rewind();

        $this->logger?->debug(sprintf(
            '[Profiling] File collection complete: %d files (%.2fms)',
            $this->fileCount,
            ($this->postCollectTime - $this->startTime) * 1000
        ));
    }

    /**
     * Called after parsing is complete.
     * Compilation starts after this event.
     */
    public function onPostParseProcess(PostParseProcess $event): void
    {
        if (!$this->enabled) {
            return;
        }

        $this->postParseTime = microtime(true);
        $this->postParseMemory = memory_get_usage(true);
        $this->documentCount = count($event->getDocuments());

        $parseTime = ($this->postParseTime - $this->startTime) * 1000;
        $avgPerFile = $this->fileCount > 0 ? $parseTime / $this->fileCount : 0;

        $this->logger?->info(sprintf(
            '[Profiling] Parsing complete: %d docs in %.2fms (avg %.2fms/file)',
            $this->documentCount,
            $parseTime,
            $avgPerFile
        ));
    }

    /**
     * Called before rendering starts.
     * Compilation is complete at this point (on first render pass).
     */
    public function onPreRenderProcess(PreRenderProcess $event): void
    {
        if (!$this->enabled) {
            return;
        }

        $this->preRenderTime = microtime(true);
        $this->renderPassCount++;

        // Only log compilation time on first render pass
        if ($this->renderPassCount === 1) {
            $compileTime = ($this->preRenderTime - $this->postParseTime) * 1000;

            $this->logger?->info(sprintf(
                '[Profiling] Compilation complete: %.2fms',
                $compileTime
            ));
        }

        $this->logger?->debug(sprintf(
            '[Profiling] Starting render pass %d: %s',
            $this->renderPassCount,
            $event->getCommand()->getOutputFormat()
        ));
    }

    /**
     * Called after rendering is complete.
     * Accumulates rendering time across all output formats.
     */
    public function onPostRenderProcess(PostRenderProcess $event): void
    {
        if (!$this->enabled) {
            return;
        }

        $this->postRenderTime = microtime(true);
        $this->postRenderMemory = memory_get_peak_usage(true);

        // Accumulate render time for this pass
        $passRenderTime = ($this->postRenderTime - $this->preRenderTime) * 1000;
        $this->totalRenderTime += $passRenderTime;

        $this->logger?->debug(sprintf(
            '[Profiling] Render pass %d complete: %.2fms (%s)',
            $this->renderPassCount,
            $passRenderTime,
            $event->getCommand()->getOutputFormat()
        ));

        // Log final results after all passes (we don't know when the last pass is,
        // so we log after each pass - the JSON file will have final accumulated values)
        $this->logResults();
    }

    /**
     * Log the profiling results.
     */
    private function logResults(): void
    {
        $totalTime = ($this->postRenderTime - $this->startTime) * 1000;
        $parseTime = ($this->postParseTime - $this->startTime) * 1000;

        // Compilation time is from end of parsing to start of FIRST render pass
        // We need to calculate this from the first preRenderTime, not the current one
        // For simplicity, we calculate it as: total - parsing - all_render_time
        $renderTime = $this->totalRenderTime;
        $compileTime = $totalTime - $parseTime - $renderTime;

        $results = [
            'total_files' => $this->fileCount,
            'total_documents' => $this->documentCount,
            'render_passes' => $this->renderPassCount,
            'timing_ms' => [
                'total' => round($totalTime, 2),
                'parsing' => round($parseTime, 2),
                'compilation' => round($compileTime, 2),
                'rendering' => round($renderTime, 2),
            ],
            'timing_percent' => [
                'parsing' => $totalTime > 0 ? round(($parseTime / $totalTime) * 100, 1) : 0,
                'compilation' => $totalTime > 0 ? round(($compileTime / $totalTime) * 100, 1) : 0,
                'rendering' => $totalTime > 0 ? round(($renderTime / $totalTime) * 100, 1) : 0,
            ],
            'per_file_avg_ms' => [
                'parsing' => $this->fileCount > 0 ? round($parseTime / $this->fileCount, 2) : 0,
                'rendering' => $this->documentCount > 0 ? round($renderTime / $this->documentCount, 2) : 0,
            ],
            'memory_mb' => [
                'start' => round($this->startMemory / 1024 / 1024, 2),
                'post_parse' => round($this->postParseMemory / 1024 / 1024, 2),
                'peak' => round($this->postRenderMemory / 1024 / 1024, 2),
            ],
        ];

        // Log summary
        $this->logger?->info(sprintf(
            '[Profiling] SUMMARY: Total %.2fms | Parse %.2fms (%.1f%%) | Compile %.2fms (%.1f%%) | Render %.2fms (%.1f%%) | Peak Memory %.2f MB',
            $results['timing_ms']['total'],
            $results['timing_ms']['parsing'],
            $results['timing_percent']['parsing'],
            $results['timing_ms']['compilation'],
            $results['timing_percent']['compilation'],
            $results['timing_ms']['rendering'],
            $results['timing_percent']['rendering'],
            $results['memory_mb']['peak']
        ));

        // Output to file if GUIDES_PROFILING_OUTPUT env var is set
        $outputPath = getenv('GUIDES_PROFILING_OUTPUT');
        if ($outputPath !== false && $outputPath !== '') {
            $json = json_encode($results, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
            if ($json !== false) {
                file_put_contents($outputPath, $json . "\n");
                $this->logger?->debug(sprintf('[Profiling] Results written to %s', $outputPath));
            }
        }
    }

    /**
     * Get the profiling results as an array.
     *
     * @return array<string, mixed>
     */
    public function getResults(): array
    {
        $totalTime = ($this->postRenderTime - $this->startTime) * 1000;
        $parseTime = ($this->postParseTime - $this->startTime) * 1000;
        $compileTime = ($this->preRenderTime - $this->postParseTime) * 1000;
        $renderTime = ($this->postRenderTime - $this->preRenderTime) * 1000;

        return [
            'total_files' => $this->fileCount,
            'total_documents' => $this->documentCount,
            'timing_ms' => [
                'total' => round($totalTime, 2),
                'parsing' => round($parseTime, 2),
                'compilation' => round($compileTime, 2),
                'rendering' => round($renderTime, 2),
            ],
            'timing_percent' => [
                'parsing' => $totalTime > 0 ? round(($parseTime / $totalTime) * 100, 1) : 0,
                'compilation' => $totalTime > 0 ? round(($compileTime / $totalTime) * 100, 1) : 0,
                'rendering' => $totalTime > 0 ? round(($renderTime / $totalTime) * 100, 1) : 0,
            ],
            'memory_mb' => [
                'peak' => round($this->postRenderMemory / 1024 / 1024, 2),
            ],
        ];
    }
}
