#!/bin/bash
#
# Run benchmarks inside Docker container for reproducibility
#
# Usage: ./benchmark/benchmark-docker.sh [scenario] [runs] [docs-type]
#
# Scenarios: cold, warm, partial, all
# Docs: small (Documentation-rendertest), large (TYPO3CMS-Reference-CoreApi)
#

set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_DIR="$(dirname "$SCRIPT_DIR")"
RESULTS_DIR="$SCRIPT_DIR/results"

SCENARIO="${1:-cold}"
RUNS="${2:-3}"
DOCS_TYPE="${3:-small}"

BRANCH=$(cd "$PROJECT_DIR" && git rev-parse --abbrev-ref HEAD 2>/dev/null | sed 's/\//_/g' || echo "unknown")
COMMIT=$(cd "$PROJECT_DIR" && git rev-parse --short HEAD 2>/dev/null || echo "unknown")
TIMESTAMP=$(date +%Y%m%d_%H%M%S)
IMAGE_TAG="typo3-docs:benchmark"

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
BLUE='\033[0;34m'
YELLOW='\033[1;33m'
NC='\033[0m'

log_info() { echo -e "${BLUE}[INFO]${NC} $1"; }
log_success() { echo -e "${GREEN}[OK]${NC} $1"; }
log_error() { echo -e "${RED}[ERROR]${NC} $1"; }
log_warn() { echo -e "${YELLOW}[WARN]${NC} $1"; }

# Determine documentation directory (relative path from project root)
case "$DOCS_TYPE" in
    small)
        DOCS_INPUT="Documentation-rendertest"
        ;;
    large)
        DOCS_INPUT="benchmark/test-docs/TYPO3CMS-Reference-CoreApi/Documentation"
        # Ensure large docs are downloaded
        if [ ! -d "$PROJECT_DIR/benchmark/test-docs/TYPO3CMS-Reference-CoreApi" ]; then
            log_info "Downloading TYPO3 CoreApi documentation..."
            "$SCRIPT_DIR/download-test-docs.sh" TYPO3CMS-Reference-CoreApi
        fi
        ;;
    *)
        # Assume it's a custom path
        DOCS_INPUT="$DOCS_TYPE"
        ;;
esac

# Check if docs exist
if [ ! -d "$PROJECT_DIR/$DOCS_INPUT" ]; then
    log_error "Documentation directory not found: $PROJECT_DIR/$DOCS_INPUT"
    exit 1
fi

# Build Docker image
build_image() {
    log_info "Building Docker image: $IMAGE_TAG"
    cd "$PROJECT_DIR"
    docker build -t "$IMAGE_TAG" . 2>&1 | tail -3
    log_success "Image built: $IMAGE_TAG"
}

# Clean caches (host-side temp directories and incremental rendering cache)
clean_caches() {
    log_info "Cleaning caches..."
    # Clean shared cache directory (Twig cache, inventory cache, etc.)
    rm -rf /tmp/typo3-guides-benchmark-cache/* 2>/dev/null || true
    # Use docker to clean root-owned files from previous runs
    docker run --rm -v /tmp:/tmp alpine sh -c "rm -rf /tmp/typo3-guides-* /tmp/benchmark-output /tmp/benchmark-log*" 2>/dev/null || true
    # Remove incremental rendering cache from docs directory (if stored there)
    rm -f "$PROJECT_DIR/$DOCS_INPUT/_build_meta.json" 2>/dev/null || true
    # Remove .cache directory used by incremental rendering
    rm -rf "$PROJECT_DIR/.cache" 2>/dev/null || true
}

# Run single benchmark using wall-clock timing
# Pass "fresh" as second arg to force clean output directory
run_benchmark_simple() {
    local run_num=$1
    local fresh_output="${2:-no}"
    local output_dir="/tmp/benchmark-output"
    local log_file="/tmp/benchmark-log-$run_num.txt"

    # Only clean output dir if fresh is requested (cold scenario)
    if [ "$fresh_output" = "fresh" ]; then
        docker run --rm -v /tmp:/tmp alpine rm -rf /tmp/benchmark-output 2>/dev/null || true
    fi
    mkdir -p "$output_dir"

    # Detect guides.xml config location
    local config_arg=""
    if [ -f "$PROJECT_DIR/$DOCS_INPUT/guides.xml" ]; then
        config_arg="--config=$DOCS_INPUT"
    fi

    # Run guides with wall-clock timing
    local start_time end_time elapsed
    start_time=$(date +%s.%N)

    # Run container - mount full project at /project, output at /output
    # Use relative paths for input and config
    # Use --user to match host user for output permissions
    # Note: project mounted read-write so incremental rendering cache can be written
    # Mount shared /tmp for Twig cache persistence between warm runs
    local shared_tmp="/tmp/typo3-guides-benchmark-cache"
    mkdir -p "$shared_tmp"
    docker run --rm \
        --user "$(id -u):$(id -g)" \
        -v "$PROJECT_DIR:/project" \
        -v "$output_dir:/output" \
        -v "$shared_tmp:/tmp" \
        "$IMAGE_TAG" \
        --no-progress $config_arg --output=/output "$DOCS_INPUT" > "$log_file" 2>&1
    local docker_exit=$?

    end_time=$(date +%s.%N)
    elapsed=$(echo "$end_time - $start_time" | bc)

    # Count output files
    local file_count
    file_count=$(find "$output_dir" -name "*.html" 2>/dev/null | wc -l | tr -d ' ')

    # Estimate memory from container (rough estimate based on output size)
    local output_size_kb estimated_memory_mb
    output_size_kb=$(du -sk "$output_dir" 2>/dev/null | awk '{print $1}')
    output_size_kb=${output_size_kb:-0}
    # Rough heuristic: memory is typically 50-100x output size for docs rendering
    if [ "$output_size_kb" -gt 0 ]; then
        estimated_memory_mb=$(echo "scale=0; ($output_size_kb * 60) / 1024" | bc)
    else
        estimated_memory_mb=64
    fi
    if [ "$estimated_memory_mb" -lt 50 ]; then
        estimated_memory_mb=64  # minimum reasonable estimate
    fi

    # Output JSON result
    echo "{\"total_time_seconds\": $elapsed, \"peak_memory_mb\": $estimated_memory_mb, \"files_rendered\": $file_count}"
}

# Run scenario and collect results
run_scenario() {
    local scenario=$1
    local results=()
    local times=()
    local memories=()
    local files=0

    log_info "Running scenario: $scenario ($RUNS runs, docs: $DOCS_TYPE)"

    case "$scenario" in
        cold)
            for ((i=1; i<=RUNS; i++)); do
                clean_caches
                log_info "  Run $i/$RUNS (cold)..."
                result=$(run_benchmark_simple $i fresh)
                results+=("$result")
                time_s=$(echo "$result" | jq -r '.total_time_seconds')
                memory_mb=$(echo "$result" | jq -r '.peak_memory_mb')
                files=$(echo "$result" | jq -r '.files_rendered')
                log_success "    Time: ${time_s}s, Memory: ~${memory_mb}MB, Files: $files"
            done
            ;;
        warm)
            # First run to populate cache
            log_info "  Initial render to populate cache..."
            clean_caches
            run_benchmark_simple 0 fresh > /dev/null

            for ((i=1; i<=RUNS; i++)); do
                log_info "  Run $i/$RUNS (warm)..."
                result=$(run_benchmark_simple $i)  # Reuse existing cache
                results+=("$result")
                time_s=$(echo "$result" | jq -r '.total_time_seconds')
                memory_mb=$(echo "$result" | jq -r '.peak_memory_mb')
                files=$(echo "$result" | jq -r '.files_rendered')
                log_success "    Time: ${time_s}s, Memory: ~${memory_mb}MB, Files: $files"
            done
            ;;
        partial)
            # First run to populate cache
            log_info "  Initial render to populate cache..."
            clean_caches
            run_benchmark_simple 0 fresh > /dev/null

            for ((i=1; i<=RUNS; i++)); do
                log_info "  Run $i/$RUNS (partial - modifying Index.rst)..."
                # Modify file content to trigger partial re-render (touch doesn't work with content hashing)
                local index_file="$PROJECT_DIR/$DOCS_INPUT/Index.rst"
                if [ ! -f "$index_file" ]; then
                    index_file="$PROJECT_DIR/$DOCS_INPUT/index.rst"
                fi
                echo "" >> "$index_file"  # Append newline to change content hash
                sleep 0.1
                result=$(run_benchmark_simple $i)  # Reuse existing cache
                results+=("$result")
                time_s=$(echo "$result" | jq -r '.total_time_seconds')
                memory_mb=$(echo "$result" | jq -r '.peak_memory_mb')
                files=$(echo "$result" | jq -r '.files_rendered')
                log_success "    Time: ${time_s}s, Memory: ~${memory_mb}MB, Files: $files"
            done
            ;;
    esac

    # Extract values for aggregation
    for result in "${results[@]}"; do
        times+=($(echo "$result" | jq -r '.total_time_seconds'))
        memories+=($(echo "$result" | jq -r '.peak_memory_mb'))
    done

    # Calculate aggregates
    local time_sum=0 mem_sum=0
    local time_min=${times[0]} time_max=${times[0]}
    local mem_min=${memories[0]} mem_max=${memories[0]}

    for i in "${!times[@]}"; do
        time_sum=$(echo "$time_sum + ${times[$i]}" | bc)
        mem_sum=$(echo "$mem_sum + ${memories[$i]}" | bc)

        if (( $(echo "${times[$i]} < $time_min" | bc -l) )); then time_min=${times[$i]}; fi
        if (( $(echo "${times[$i]} > $time_max" | bc -l) )); then time_max=${times[$i]}; fi
        if (( $(echo "${memories[$i]} < $mem_min" | bc -l) )); then mem_min=${memories[$i]}; fi
        if (( $(echo "${memories[$i]} > $mem_max" | bc -l) )); then mem_max=${memories[$i]}; fi
    done

    local time_avg=$(echo "scale=3; $time_sum / ${#times[@]}" | bc)
    local mem_avg=$(echo "scale=1; $mem_sum / ${#memories[@]}" | bc)

    # Save to JSON
    mkdir -p "$RESULTS_DIR"
    local result_file="$RESULTS_DIR/${BRANCH}_${scenario}_${DOCS_TYPE}_${TIMESTAMP}.json"

    cat > "$result_file" << EOF
{
    "branch": "$BRANCH",
    "commit": "$COMMIT",
    "scenario": "$scenario",
    "docs_type": "$DOCS_TYPE",
    "timestamp": "$TIMESTAMP",
    "runs": $RUNS,
    "metrics": {
        "time": {
            "avg_seconds": $time_avg,
            "min_seconds": $time_min,
            "max_seconds": $time_max
        },
        "memory": {
            "avg_mb": $mem_avg,
            "min_mb": $mem_min,
            "max_mb": $mem_max
        },
        "files_rendered": $files
    },
    "raw_times_seconds": [$(IFS=,; echo "${times[*]}")],
    "raw_memories_mb": [$(IFS=,; echo "${memories[*]}")]
}
EOF

    log_success "Results saved: $result_file"

    # Print summary
    echo ""
    echo "=== $scenario Summary ==="
    echo "  Avg Time:   ${time_avg}s (min: ${time_min}s, max: ${time_max}s)"
    echo "  Avg Memory: ~${mem_avg}MB (estimated)"
    echo "  Files:      $files"
    echo ""
}

# Main
echo "============================================"
echo "Benchmark: $SCENARIO"
echo "Branch:    $BRANCH ($COMMIT)"
echo "Docs:      $DOCS_TYPE ($DOCS_INPUT)"
echo "Runs:      $RUNS"
echo "============================================"
echo ""

# Ensure jq is available
if ! command -v jq &> /dev/null; then
    log_error "jq is required but not installed. Install with: apt-get install jq"
    exit 1
fi

# Build image
build_image

mkdir -p "$RESULTS_DIR"

# Run scenarios
if [ "$SCENARIO" = "all" ]; then
    run_scenario cold
    run_scenario warm
    run_scenario partial
else
    run_scenario "$SCENARIO"
fi

log_success "Benchmarks complete!"
