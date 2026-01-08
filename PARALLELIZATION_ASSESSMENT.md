# Deep Assessment: Compilation Phase Parallelization

## Executive Summary

The compilation phase can be parallelized using a **three-phase approach with deferred resolution**:
1. **Parallel Collection** - Each document independently collects metadata into temporary structures
2. **Sequential Merge** - Fast merge of all collected data into shared ProjectNode
3. **Parallel Resolution** - Each document independently resolves cross-references

This approach uses placeholders/deferred resolution to break the apparent sequential dependency.

---

## Current Architecture Analysis

### Compilation Flow

```
Compiler::run(documents, CompilerContext)
  └─> foreach $pass in $passes (priority-ordered):
        └─> $pass->run(documents, compilerContext)
              └─> For TransformerPass: iterate all documents
                    └─> For each document: traverse nodes with transformers
```

### Priority Order of Passes (Higher = Runs First)

| Priority | Pass/Transformer | Type | SharedState Access |
|----------|------------------|------|-------------------|
| 40,000 | ClassNodeTransformer | Transform | None |
| 30,000 | MoveAnchorTransformer | Transform | None |
| 30,000 | FootNodeNumberedTransformer | Transform | None |
| 30,000 | VariableInlineNodeTransformer | Transform | Read ProjectNode.variables |
| 20,000 | ImplicitHyperlinkTargetPass | Pass | None (document-local) |
| 20,000 | TocNodeReplacementTransformer | Transform | None |
| 20,000 | FootNodeNamedTransformer | Transform | None |
| 20,000 | CitationTargetTransformer | Transform | **Write ProjectNode.citationTargets** |
| 5,000 | DocumentEntryRegistrationTransformer | Transform | **Write ProjectNode.documentEntries** |
| 5,000 | CollectLinkTargetsTransformer | Transform | **Write ProjectNode.internalLinkTargets** |
| 4,900 | SectionEntryRegistrationTransformer | Transform | **Write DocumentEntry.sections** |
| 4,500 | InternalMenuEntryNodeTransformer | Transform | **Read ProjectNode.documentEntries** |
| 4,500 | ContentsMenuEntryNodeTransformer | Transform | **Read DocumentEntry.sections** |
| 4,500 | ExternalMenuEntryNodeTransformer | Transform | None |
| 4,000 | GlobMenuEntryNodeTransformer | Transform | **Read ProjectNode.documentEntries** |
| 4,000 | CollectPrefixLinkTargetsTransformer | Transform | **Write ProjectNode.internalLinkTargets** |
| 3,200 | ToctreeSortingTransformer | Transform | None |
| 3,000 | SubInternalMenuEntryNodeTransformer | Transform | **Read ProjectNode.documentEntries** |
| 3,000 | DocumentBlockNodeTransformer | Transform | None |
| 2,000 | FootnoteInlineNodeTransformer | Transform | Read document footnotes |
| 2,000 | CitationInlineNodeTransformer | Transform | **Read ProjectNode.citationTargets** |
| 1,000 | TocNodeTransformer | Transform | Write DocumentNode.tocNodes |
| 1,000 | ListNodeTransformer | Transform | None |
| 1,000 | RawNodeEscapeTransformer | Transform | None |
| 20 | GlobalMenuPass | Pass | **Read ProjectNode.documentEntries** |
| 20 | AutomaticMenuPass | Pass | **Write DocumentEntry.parent/children** |
| 20 | ToctreeValidationPass | Pass | **Read ProjectNode.documentEntries** |

---

## Shared State Analysis

### ProjectNode - Central State Container

```php
final class ProjectNode {
    // WRITTEN during compilation
    private array $documentEntries = [];       // DocumentEntryNode[]
    private array $internalLinkTargets = [];   // array<string, array<string, InternalTarget>>
    private array $citationTargets = [];       // array<string, CitationTarget>
    private array $globalMenues = [];          // NavMenuNode[]

    // READ-ONLY during compilation
    private array $variables = [];             // Set before compilation
}
```

### Dependency Graph

```
PHASE 1: Document Registration (Priority 5000)
┌─────────────────────────────────────────────────────┐
│ DocumentEntryRegistrationTransformer                │
│   - Creates DocumentEntryNode for each document     │
│   - Writes to ProjectNode.documentEntries           │
│   - NO cross-document dependencies                  │
├─────────────────────────────────────────────────────┤
│ CollectLinkTargetsTransformer                       │
│   - Collects anchors, sections, link targets        │
│   - Writes to ProjectNode.internalLinkTargets       │
│   - NO cross-document dependencies                  │
└─────────────────────────────────────────────────────┘
                          │
                          ▼ DEPENDS ON
PHASE 2: Section Registration (Priority 4900)
┌─────────────────────────────────────────────────────┐
│ SectionEntryRegistrationTransformer                 │
│   - Needs DocumentEntry to exist                    │
│   - Adds sections to DocumentEntry                  │
│   - NO cross-document dependencies                  │
└─────────────────────────────────────────────────────┘
                          │
                          ▼ DEPENDS ON
PHASE 3: Menu Resolution (Priority 4500-3000)
┌─────────────────────────────────────────────────────┐
│ InternalMenuEntryNodeTransformer                    │
│   - READS ProjectNode.documentEntries               │
│   - Resolves :doc: references to actual documents   │
│   - CROSS-DOCUMENT DEPENDENCY                       │
├─────────────────────────────────────────────────────┤
│ GlobMenuEntryNodeTransformer                        │
│   - READS ProjectNode.documentEntries               │
│   - Expands glob patterns to document lists         │
│   - CROSS-DOCUMENT DEPENDENCY                       │
├─────────────────────────────────────────────────────┤
│ SubInternalMenuEntryNodeTransformer                 │
│   - READS ProjectNode.documentEntries               │
│   - Builds submenu structures                       │
│   - CROSS-DOCUMENT DEPENDENCY                       │
└─────────────────────────────────────────────────────┘
                          │
                          ▼ DEPENDS ON
PHASE 4: Citation Resolution (Priority 2000)
┌─────────────────────────────────────────────────────┐
│ CitationInlineNodeTransformer                       │
│   - READS ProjectNode.citationTargets               │
│   - Resolves citation references                    │
│   - CROSS-DOCUMENT DEPENDENCY                       │
└─────────────────────────────────────────────────────┘
                          │
                          ▼ DEPENDS ON
PHASE 5: Menu Structure (Priority 20)
┌─────────────────────────────────────────────────────┐
│ AutomaticMenuPass                                   │
│   - WRITES DocumentEntry.parent/children            │
│   - Builds document hierarchy                       │
│   - CROSS-DOCUMENT DEPENDENCY (parent/child links) │
├─────────────────────────────────────────────────────┤
│ GlobalMenuPass                                      │
│   - READS DocumentEntries with parent/children      │
│   - Builds global navigation                        │
│   - CROSS-DOCUMENT DEPENDENCY                       │
└─────────────────────────────────────────────────────┘
```

---

## Placeholder-Based Deferred Resolution Approach

### Concept

Instead of resolving cross-document references immediately, we:
1. **Collect** all data that needs to be written (link targets, document entries, etc.)
2. **Mark** nodes that need cross-document resolution with **placeholders**
3. **Merge** collected data from all documents into ProjectNode
4. **Resolve** placeholders by replacing them with actual data

### Key Insight

The transformers can be categorized into three groups:

| Group | Action | Parallelizable |
|-------|--------|---------------|
| **Collectors** | Add data to ProjectNode | YES (collect locally, merge later) |
| **Resolvers** | Read from ProjectNode | YES (after merge phase) |
| **Independent** | No shared state | YES (always) |

### Implementation Strategy

#### Phase A: Parallel Collection (Per-Document)

Each document runs collectors independently, storing results in a **DocumentCompilationResult**:

```php
class DocumentCompilationResult {
    public array $documentEntries = [];      // DocumentEntryNode to add
    public array $linkTargets = [];          // InternalTarget[] to add
    public array $citationTargets = [];      // CitationTarget[] to add
    public array $sectionEntries = [];       // SectionEntryNode[] per document

    // Placeholder markers for deferred resolution
    public array $unresolvedMenuEntries = [];  // MenuEntryPlaceholder[]
    public array $unresolvedCitations = [];    // CitationPlaceholder[]
}
```

**Transformers in this phase:**
- DocumentEntryRegistrationTransformer → writes to result.documentEntries
- CollectLinkTargetsTransformer → writes to result.linkTargets
- CitationTargetTransformer → writes to result.citationTargets
- SectionEntryRegistrationTransformer → writes to result.sectionEntries

#### Phase B: Sequential Merge (Fast)

Merge all DocumentCompilationResults into ProjectNode:

```php
foreach ($results as $result) {
    foreach ($result->documentEntries as $entry) {
        $projectNode->addDocumentEntry($entry);
    }
    foreach ($result->linkTargets as [$anchor, $target]) {
        $projectNode->addLinkTarget($anchor, $target);
    }
    // ... etc
}
```

This is O(n) where n = total entries, very fast.

#### Phase C: Parallel Resolution (Per-Document)

Each document runs resolvers independently, now that all data is in ProjectNode:

**Transformers in this phase:**
- InternalMenuEntryNodeTransformer → resolves menu references
- GlobMenuEntryNodeTransformer → expands globs
- SubInternalMenuEntryNodeTransformer → builds submenus
- CitationInlineNodeTransformer → resolves citations

#### Phase D: Sequential Finalization

Final passes that build global structures:
- AutomaticMenuPass → builds document hierarchy
- GlobalMenuPass → builds global navigation

---

## Alternative Approach: Placeholder Nodes

Instead of result objects, use **placeholder nodes** in the AST:

```php
// During Phase A, instead of resolving:
class UnresolvedDocRefNode extends InternalMenuEntryNode {
    private string $targetPath;  // Path to resolve later
    // ... marker that this needs resolution
}

// During Phase C, replace with resolved node:
class ResolvedMenuEntryNodeTransformer implements NodeTransformer {
    public function enterNode(Node $node, CompilerContext $ctx): Node {
        if ($node instanceof UnresolvedDocRefNode) {
            $entry = $ctx->getProjectNode()->getDocumentEntry($node->targetPath);
            return new InternalMenuEntryNode(
                $entry->getFile(),
                $entry->getTitle(),
                // ...
            );
        }
        return $node;
    }
}
```

### Advantages of Placeholder Approach
1. **No result object complexity** - AST is the data structure
2. **Lazy resolution** - Only resolve what's needed
3. **Clear separation** - Placeholders make unresolved state explicit
4. **Backward compatible** - Existing code sees final resolved nodes

---

## Validation: Cross-Reference Resolution During Rendering

**Key Finding:** Most cross-document link resolution happens during **rendering**, not compilation!

```php
// In ReferenceResolvers (rendering phase):
$target = $renderContext->getProjectNode()->getInternalTarget($anchor, $linkType);
$node->setUrl($this->urlGenerator->generateUrl($target));
```

This means:
1. Compilation only needs to **collect** link targets
2. Resolution of `:ref:`, `:doc:` etc. happens during rendering
3. The rendering phase (ForkingRenderer) is already parallel

**Implication:** The main compilation parallelization win is in the **collection phase**.

---

## Detailed Implementation Plan

### Step 1: Create DocumentCompilationResult

```php
namespace phpDocumentor\Guides\Compiler;

final class DocumentCompilationResult
{
    /** @var array<string, DocumentEntryNode> */
    public array $documentEntries = [];

    /** @var list<array{string, InternalTarget}> */
    public array $linkTargets = [];

    /** @var array<string, CitationTarget> */
    public array $citationTargets = [];

    /** @var array<string, list<SectionEntryNode>> */
    public array $sectionEntries = [];
}
```

### Step 2: Create ParallelCollectionPhase

```php
final class ParallelCollectionPhase
{
    private array $collectors;  // Transformers that collect data

    public function run(array $documents, CompilerContext $ctx): array
    {
        $results = [];

        // Fork for each document
        foreach ($documents as $document) {
            $pid = pcntl_fork();
            if ($pid === 0) {
                // Child: run collectors, write result to temp file
                $result = $this->collectForDocument($document, $ctx);
                file_put_contents($tempFile, serialize($result));
                exit(0);
            }
            $pids[] = $pid;
        }

        // Wait and collect results
        foreach ($pids as $i => $pid) {
            pcntl_waitpid($pid, $status);
            $results[$i] = unserialize(file_get_contents($tempFiles[$i]));
        }

        return $results;
    }

    private function collectForDocument(DocumentNode $doc, CompilerContext $ctx): DocumentCompilationResult
    {
        $result = new DocumentCompilationResult();

        // Run collection transformers with result collector
        foreach ($this->collectors as $collector) {
            $collector->collect($doc, $ctx, $result);
        }

        return $result;
    }
}
```

### Step 3: Create MergePhase

```php
final class MergePhase
{
    public function run(array $results, ProjectNode $projectNode): void
    {
        foreach ($results as $result) {
            // Merge document entries
            foreach ($result->documentEntries as $entry) {
                $projectNode->addDocumentEntry($entry);
            }

            // Merge link targets (handle duplicates)
            foreach ($result->linkTargets as [$anchor, $target]) {
                try {
                    $projectNode->addLinkTarget($anchor, $target);
                } catch (DuplicateLinkAnchorException $e) {
                    // Log warning, first wins
                }
            }

            // Merge citations
            foreach ($result->citationTargets as $citation) {
                $projectNode->addCitationTarget($citation);
            }
        }
    }
}
```

### Step 4: Create ParallelResolutionPhase

```php
final class ParallelResolutionPhase
{
    private array $resolvers;  // Transformers that resolve references

    public function run(array $documents, CompilerContext $ctx): array
    {
        // Now ProjectNode has all data - run resolvers in parallel
        $resolved = [];

        foreach ($documents as $document) {
            $pid = pcntl_fork();
            if ($pid === 0) {
                $doc = $this->resolveDocument($document, $ctx);
                file_put_contents($tempFile, serialize($doc));
                exit(0);
            }
            $pids[] = $pid;
        }

        // Collect resolved documents
        foreach ($pids as $i => $pid) {
            pcntl_waitpid($pid, $status);
            $resolved[$i] = unserialize(file_get_contents($tempFiles[$i]));
        }

        return $resolved;
    }
}
```

### Step 5: Create ParallelCompiler

```php
final class ParallelCompiler
{
    public function __construct(
        private ParallelCollectionPhase $collectionPhase,
        private MergePhase $mergePhase,
        private ParallelResolutionPhase $resolutionPhase,
        private array $finalizationPasses,  // Sequential final passes
    ) {}

    public function run(array $documents, CompilerContext $ctx): array
    {
        // Phase A: Parallel collection
        $results = $this->collectionPhase->run($documents, $ctx);

        // Phase B: Sequential merge
        $this->mergePhase->run($results, $ctx->getProjectNode());

        // Phase C: Parallel resolution
        $documents = $this->resolutionPhase->run($documents, $ctx);

        // Phase D: Sequential finalization
        foreach ($this->finalizationPasses as $pass) {
            $documents = $pass->run($documents, $ctx);
        }

        return $documents;
    }
}
```

### Step 6: Categorize Existing Transformers

**Collection Phase (Parallel):**
- DocumentEntryRegistrationTransformer (modified to collect)
- CollectLinkTargetsTransformer (modified to collect)
- CollectPrefixLinkTargetsTransformer (modified to collect)
- CitationTargetTransformer (modified to collect)
- SectionEntryRegistrationTransformer (modified to collect)

**Independent Phase (Can run anytime, parallel):**
- ClassNodeTransformer
- MoveAnchorTransformer
- FootNodeNumberedTransformer
- FootNodeNamedTransformer
- TocNodeReplacementTransformer
- ToctreeSortingTransformer
- DocumentBlockNodeTransformer
- ListNodeTransformer
- RawNodeEscapeTransformer
- TocNodeTransformer

**Resolution Phase (After merge, parallel):**
- InternalMenuEntryNodeTransformer
- ContentsMenuEntryNodeTransformer
- GlobMenuEntryNodeTransformer
- SubInternalMenuEntryNodeTransformer
- CitationInlineNodeTransformer
- VariableInlineNodeTransformer (reads variables)

**Finalization Phase (Sequential):**
- ImplicitHyperlinkTargetPass (already document-local, could be parallel)
- AutomaticMenuPass (builds cross-doc hierarchy)
- GlobalMenuPass (builds global menus)
- ToctreeValidationPass (validation only)

---

## Performance Projection

### Current Flow (Sequential)
```
Parse 100 docs: 3s (already parallel via ParallelParseDirectoryHandler)
Compile 100 docs: 4s (sequential)
Render 100 docs: 3s (already parallel via ForkingRenderer)
Total: 10s
```

### Proposed Flow (Parallel Compilation)
```
Parse 100 docs: 3s (parallel)
Compile - Collection: 0.5s (parallel, 8 workers)
Compile - Merge: 0.1s (sequential, O(n))
Compile - Resolution: 0.5s (parallel, 8 workers)
Compile - Finalization: 0.5s (sequential, few passes)
Render 100 docs: 3s (parallel)
Total: 7.6s (24% improvement on total, 60% on compilation)
```

### Larger Projects (500+ docs)
The improvement scales with document count since:
- Collection phase is O(docs/workers)
- Merge phase is O(docs) but simple operations
- Resolution phase is O(docs/workers)

---

## Risk Analysis

### Serialization Overhead
**Risk:** pcntl_fork + serialize/unserialize has overhead
**Mitigation:** Only fork if doc count > threshold (e.g., 20)

### State Mutation in Transformers
**Risk:** Some transformers have internal state (e.g., SplStack in CollectLinkTargetsTransformer)
**Mitigation:** Create fresh transformer instances per fork (copy-on-write handles immutable state)

### Error Handling
**Risk:** Child process errors need propagation
**Mitigation:** Capture errors in result structure, aggregate in merge phase

### Memory Usage
**Risk:** Forking duplicates memory
**Mitigation:** Fork with minimal context, copy-on-write reduces actual duplication

---

## Consensus Points

### Agreed Approach
1. **Three-phase model** (Collect → Merge → Resolve) is sound
2. **Placeholder nodes** provide clean AST representation
3. **pcntl_fork** is the right mechanism (already proven in ForkingRenderer)
4. **Effort is not a concern** - correctness and performance are priorities

### Implementation Order
1. Create DocumentCompilationResult structure
2. Modify collection transformers to use result object
3. Implement merge phase
4. Modify resolution transformers
5. Create ParallelCompiler orchestrator
6. Add configuration option (parallel vs sequential)
7. Benchmark and tune worker count

### Testing Strategy
1. Unit tests for each modified transformer
2. Integration tests comparing parallel vs sequential output
3. Performance benchmarks on various project sizes
4. Edge case tests (empty projects, single doc, circular refs)

---

## Conclusion

**The compilation phase CAN be parallelized** using the deferred resolution approach:

1. **Collection** of link targets, document entries, citations - **PARALLELIZABLE**
2. **Merge** of collected data - **SEQUENTIAL but FAST (O(n))**
3. **Resolution** of cross-references - **PARALLELIZABLE**
4. **Finalization** (menu building) - **SEQUENTIAL (few passes)**

The placeholder-based approach elegantly breaks the apparent sequential dependency by deferring resolution until all data is available.

Expected improvement: **20-30% reduction in total compilation time**, scaling better with larger documentation projects.
