<?php

declare(strict_types=1);

namespace T3Docs\GuidesExtension\Compiler;

use phpDocumentor\Guides\Compiler\Compiler;
use phpDocumentor\Guides\Compiler\CompilerPass;
use phpDocumentor\Guides\Compiler\NodeTransformers\NodeTransformerFactory;
use phpDocumentor\Guides\Handlers\CompileDocumentsCommand;
use phpDocumentor\Guides\Nodes\DocumentNode;
use Psr\Log\LoggerInterface;
use T3Docs\GuidesExtension\Compiler\Cache\IncrementalBuildCache;

/**
 * Handler for CompileDocumentsCommand that supports parallel compilation.
 *
 * This handler wraps the standard compilation process and optionally enables
 * parallel processing when conditions are favorable (sufficient documents,
 * pcntl available, etc.).
 *
 * When parallel compilation is enabled, the compilation is split into phases:
 * 1. Parallel Collection - collect metadata in parallel
 * 2. Sequential Merge - merge collected data (fast)
 * 3. Parallel Resolution - resolve cross-references in parallel
 * 4. Sequential Finalization - finalize menus and structures
 */
final class ParallelCompileDocumentsHandler
{
    private readonly ParallelCompiler $parallelCompiler;

    /**
     * @param iterable<CompilerPass> $passes
     */
    public function __construct(
        Compiler $sequentialCompiler,
        iterable $passes,
        NodeTransformerFactory $nodeTransformerFactory,
        ?IncrementalBuildCache $incrementalCache = null,
        ?LoggerInterface $logger = null,
    ) {
        // Create parallel compiler with injected sequential compiler for fallback
        $this->parallelCompiler = new ParallelCompiler(
            $sequentialCompiler,
            $passes,
            $nodeTransformerFactory,
            $incrementalCache,
            $logger,
        );
    }

    /**
     * @return DocumentNode[]
     */
    public function handle(CompileDocumentsCommand $command): array
    {
        return $this->parallelCompiler->run(
            $command->getDocuments(),
            $command->getCompilerContext()
        );
    }

    /**
     * Enable or disable parallel compilation.
     */
    public function setParallelEnabled(bool $enabled): void
    {
        $this->parallelCompiler->setParallelEnabled($enabled);
    }

    /**
     * Check if parallel compilation is enabled.
     */
    public function isParallelEnabled(): bool
    {
        return $this->parallelCompiler->isParallelEnabled();
    }
}
