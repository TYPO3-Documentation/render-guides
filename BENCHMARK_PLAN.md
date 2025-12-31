# Performance Benchmark Plan

## Objective

Measure and document the performance improvements from incremental rendering, providing concrete numbers for the PR.

## Metrics to Measure

1. **Render Time** (seconds)
2. **Peak Memory Usage** (MB)
3. **Files Processed** (count)
4. **Files Skipped** (incremental only)

## Test Scenarios

| Scenario | Description | Expected Improvement |
|----------|-------------|---------------------|
| Cold Render | First render, no cache | Baseline (similar) |
| Warm Render | Re-render, no changes | Major (skip all) |
| Partial Change | 1-2 files modified | Significant (skip most) |
| Config Change | guides.xml modified | None (full re-render) |

## Test Documentation Projects

1. **Small**: `Documentation/` (14 files) - quick validation
2. **Medium**: `Documentation-rendertest/` (~50 files) - included in repo
3. **Large**: External TYPO3 docs (e.g., reference-coreapi) - real-world test

## Implementation Options

### Option A: Standalone Benchmark Script
```bash
./benchmark.sh [branch] [scenario]
```
- Simple bash script with `time` and memory tracking
- Runs in Docker for reproducibility
- Outputs CSV/JSON for comparison

### Option B: PHPUnit Performance Tests
- `@group benchmark` tests
- Use PHPUnit's `assertLessThan` for regression detection
- Integrated with CI (can be slow)

### Option C: Makefile Targets
```make
make benchmark-cold
make benchmark-warm
make benchmark-compare
```
- Uses existing Docker setup
- Easy to run manually
- Outputs formatted report

## Recommended Approach: Hybrid (A + C)

1. **Makefile targets** for easy execution
2. **Bash script** for actual measurement logic
3. **Docker container** for reproducibility
4. **JSON output** for programmatic comparison
5. **Markdown report** for PR documentation

## Benchmark Script Design

```
benchmark/
├── run-benchmark.sh      # Main benchmark runner
├── compare-branches.sh   # Compare main vs feature
├── scenarios/
│   ├── cold.sh          # Cold render scenario
│   ├── warm.sh          # Warm render (re-run)
│   └── partial.sh       # Modify file, re-render
└── results/
    └── .gitkeep
```

## Measurements Approach

### Time Measurement
```bash
/usr/bin/time -v ./vendor/bin/guides ... 2>&1
# Extract: "Elapsed (wall clock) time"
```

### Memory Measurement
```bash
# From /usr/bin/time -v output:
# "Maximum resident set size (kbytes)"
```

### PHP-level Metrics
```php
$start = hrtime(true);
// ... render ...
$elapsed = (hrtime(true) - $start) / 1e9;
$memory = memory_get_peak_usage(true);
```

## Output Format

### JSON (machine-readable)
```json
{
  "branch": "feature/php-8.5-only",
  "scenario": "warm",
  "project": "Documentation-rendertest",
  "metrics": {
    "time_seconds": 2.34,
    "memory_mb": 128.5,
    "files_total": 51,
    "files_rendered": 0,
    "files_skipped": 51
  }
}
```

### Markdown (for PR)
```markdown
| Scenario | main | feature | Improvement |
|----------|------|---------|-------------|
| Cold     | 5.2s | 5.1s    | ~2%         |
| Warm     | 5.2s | 0.8s    | 85%         |
| Partial  | 5.2s | 1.2s    | 77%         |
```

## Execution Plan

### Phase 1: Create Benchmark Infrastructure
- [ ] Create `benchmark/run-benchmark.sh`
- [ ] Add Makefile targets
- [ ] Test with local Documentation/

### Phase 2: Measure Current Branch
- [ ] Run cold/warm/partial scenarios
- [ ] Record results in JSON

### Phase 3: Compare with Main
- [ ] Checkout main, run same scenarios
- [ ] Generate comparison report

### Phase 4: Add to PR
- [ ] Include benchmark results in PR description
- [ ] Add benchmark scripts to repo (optional)

## Docker Command Template

```bash
docker run --rm \
  -v $(pwd):/project \
  -w /project \
  ghcr.io/typo3-documentation/render-guides:latest \
  /usr/bin/time -v \
  ./vendor/bin/guides \
    --no-progress \
    --output=/tmp/output \
    Documentation-rendertest
```

## Notes

- Run multiple times (3-5) and average for accuracy
- Clear caches between cold runs
- Use same hardware/container for fair comparison
- Document system specs in results
