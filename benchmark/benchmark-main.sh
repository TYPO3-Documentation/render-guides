#!/bin/bash
#
# Run benchmarks for main branch using the official TYPO3 render-guides container
#
# Usage: ./benchmark/benchmark-main.sh [scenario] [runs] [docs-type]
#
# Note: Memory metrics use /usr/bin/time since PHP profiling isn't available
# in the official container. Values may be less accurate than feature branch.
#

set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_DIR="$(dirname "$SCRIPT_DIR")"
RESULTS_DIR="$SCRIPT_DIR/results"

SCENARIO="${1:-cold}"
RUNS="${2:-3}"
DOCS_TYPE="${3:-small}"

BRANCH="main"
COMMIT="official"
TIMESTAMP=$(date +%Y%m%d_%H%M%S)
IMAGE="ghcr.io/typo3-documentation/render-guides:latest"

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

# Determine documentation directory
case "$DOCS_TYPE" in
    small)
        DOCS_INPUT="Documentation-rendertest"
        ;;
    large)
        DOCS_INPUT="benchmark/test-docs/TYPO3CMS-Reference-CoreApi/Documentation"
        if [ ! -d "$PROJECT_DIR/benchmark/test-docs/TYPO3CMS-Reference-CoreApi" ]; then
            log_info "Downloading TYPO3 CoreApi documentation..."
            "$SCRIPT_DIR/download-test-docs.sh" TYPO3CMS-Reference-CoreApi
        fi
        ;;
    changelog)
        DOCS_INPUT="benchmark/test-docs/TYPO3-Core-Changelog/typo3/sysext/core/Documentation"
        if [ ! -d "$PROJECT_DIR/benchmark/test-docs/TYPO3-Core-Changelog" ]; then
            log_info "Downloading TYPO3 Core Changelog documentation..."
            "$SCRIPT_DIR/download-test-docs.sh" TYPO3-Core-Changelog
        fi
        ;;
    *)
        DOCS_INPUT="$DOCS_TYPE"
        ;;
esac

if [ ! -d "$PROJECT_DIR/$DOCS_INPUT" ]; then
    log_error "Documentation directory not found: $PROJECT_DIR/$DOCS_INPUT"
    exit 1
fi

# Pull latest official image
log_info "Pulling official container: $IMAGE"
docker pull "$IMAGE" 2>&1 | tail -2

# Clean caches
clean_caches() {
    log_info "Cleaning caches..."
    rm -rf /tmp/typo3-main-benchmark/* 2>/dev/null || true
    docker run --rm -v /tmp:/tmp alpine sh -c "rm -rf /tmp/typo3-main-* /tmp/main-benchmark-*" 2>/dev/null || true
}

# Run single benchmark
# Mount project to /project and specify docs path as input
run_benchmark() {
    local run_num=$1
    local fresh_output="${2:-no}"
    local output_dir="/tmp/main-benchmark-output"
    local time_file="/tmp/main-benchmark-time-$run_num.txt"

    if [ "$fresh_output" = "fresh" ]; then
        docker run --rm -v /tmp:/tmp alpine rm -rf /tmp/main-benchmark-output 2>/dev/null || true
    fi
    mkdir -p "$output_dir"

    # Mount project root to /project, output to separate /output dir
    # Use /usr/bin/time wrapper for metrics
    docker run --rm \
        --user "$(id -u):$(id -g)" \
        -v "$PROJECT_DIR:/project:ro" \
        -v "$output_dir:/output" \
        --entrypoint /usr/bin/time \
        "$IMAGE" \
        -v php /opt/guides/vendor/bin/guides --no-progress --output=/output "$DOCS_INPUT" \
        > /dev/null 2> "$time_file" || true

    # Parse metrics
    local elapsed user_time sys_time peak_memory_kb cpu_percent raw_elapsed
    # Handle both time formats: "0:05.53" (h:mm:ss or m:ss) and "0m 0.25s"
    raw_elapsed=$(grep "Elapsed (wall clock)" "$time_file" 2>/dev/null | sed 's/.*): //')
    if echo "$raw_elapsed" | grep -q 'm'; then
        # Format: "0m 0.25s" - extract minutes and seconds
        elapsed=$(echo "$raw_elapsed" | sed 's/m/ /;s/s//' | awk '{print $1*60 + $2}')
    else
        # Format: "0:05.53" (h:mm:ss or m:ss)
        elapsed=$(echo "$raw_elapsed" | awk -F: '{if (NF==3) print $1*3600+$2*60+$3; else if (NF==2) print $1*60+$2; else print $1}')
    fi
    elapsed=${elapsed:-0}
    user_time=$(grep "User time" "$time_file" 2>/dev/null | awk '{print $NF}' || echo "0")
    sys_time=$(grep "System time" "$time_file" 2>/dev/null | awk '{print $NF}' || echo "0")
    peak_memory_kb=$(grep "Maximum resident set size" "$time_file" 2>/dev/null | awk '{print $NF}' || echo "0")
    cpu_percent=$(grep "Percent of CPU" "$time_file" 2>/dev/null | sed 's/.*: //' | tr -d '%' || echo "0")

    local peak_memory_mb cpu_time
    peak_memory_mb=$(echo "scale=1; ${peak_memory_kb:-0} / 1024" | bc)
    cpu_time=$(echo "scale=2; ${user_time:-0} + ${sys_time:-0}" | bc)

    local file_count
    file_count=$(find "$output_dir" -name "*.html" 2>/dev/null | wc -l | tr -d ' ')

    echo "{\"total_time_seconds\": ${elapsed:-0}, \"cpu_time_seconds\": $cpu_time, \"cpu_percent\": ${cpu_percent:-0}, \"peak_memory_mb\": $peak_memory_mb, \"files_rendered\": $file_count}"
}

# Run scenario
run_scenario() {
    local scenario=$1
    local results=()
    local times=()
    local cpu_times=()
    local cpu_percents=()
    local memories=()
    local files=0

    log_info "Running scenario: $scenario ($RUNS runs, docs: $DOCS_TYPE, branch: main)"

    case "$scenario" in
        cold)
            for ((i=1; i<=RUNS; i++)); do
                clean_caches
                log_info "  Run $i/$RUNS (cold)..."
                result=$(run_benchmark $i fresh)
                results+=("$result")
                time_s=$(echo "$result" | jq -r '.total_time_seconds')
                cpu_pct=$(echo "$result" | jq -r '.cpu_percent')
                memory_mb=$(echo "$result" | jq -r '.peak_memory_mb')
                files=$(echo "$result" | jq -r '.files_rendered')
                log_success "    Time: ${time_s}s, CPU: ${cpu_pct}%, Memory: ${memory_mb}MB, Files: $files"
            done
            ;;
        warm)
            log_info "  Initial render to populate cache..."
            clean_caches
            run_benchmark 0 fresh > /dev/null

            for ((i=1; i<=RUNS; i++)); do
                log_info "  Run $i/$RUNS (warm)..."
                result=$(run_benchmark $i)
                results+=("$result")
                time_s=$(echo "$result" | jq -r '.total_time_seconds')
                cpu_pct=$(echo "$result" | jq -r '.cpu_percent')
                memory_mb=$(echo "$result" | jq -r '.peak_memory_mb')
                files=$(echo "$result" | jq -r '.files_rendered')
                log_success "    Time: ${time_s}s, CPU: ${cpu_pct}%, Memory: ${memory_mb}MB, Files: $files"
            done
            ;;
    esac

    # Aggregate results
    for result in "${results[@]}"; do
        times+=($(echo "$result" | jq -r '.total_time_seconds'))
        cpu_times+=($(echo "$result" | jq -r '.cpu_time_seconds'))
        cpu_percents+=($(echo "$result" | jq -r '.cpu_percent'))
        memories+=($(echo "$result" | jq -r '.peak_memory_mb'))
    done

    local time_sum=0 cpu_sum=0 cpu_pct_sum=0 mem_sum=0
    local time_min=${times[0]} time_max=${times[0]}
    local mem_min=${memories[0]} mem_max=${memories[0]}

    for i in "${!times[@]}"; do
        time_sum=$(echo "$time_sum + ${times[$i]}" | bc)
        cpu_sum=$(echo "$cpu_sum + ${cpu_times[$i]}" | bc)
        cpu_pct_sum=$(echo "$cpu_pct_sum + ${cpu_percents[$i]}" | bc)
        mem_sum=$(echo "$mem_sum + ${memories[$i]}" | bc)

        if (( $(echo "${times[$i]} < $time_min" | bc -l) )); then time_min=${times[$i]}; fi
        if (( $(echo "${times[$i]} > $time_max" | bc -l) )); then time_max=${times[$i]}; fi
        if (( $(echo "${memories[$i]} < $mem_min" | bc -l) )); then mem_min=${memories[$i]}; fi
        if (( $(echo "${memories[$i]} > $mem_max" | bc -l) )); then mem_max=${memories[$i]}; fi
    done

    local time_avg=$(echo "scale=3; $time_sum / ${#times[@]}" | bc)
    local cpu_avg=$(echo "scale=2; $cpu_sum / ${#cpu_times[@]}" | bc)
    local cpu_pct_avg=$(echo "scale=0; $cpu_pct_sum / ${#cpu_percents[@]}" | bc)
    local mem_avg=$(echo "scale=1; $mem_sum / ${#memories[@]}" | bc)

    # Save results
    mkdir -p "$RESULTS_DIR"
    local result_file="$RESULTS_DIR/main_${scenario}_${DOCS_TYPE}_${TIMESTAMP}.json"

    cat > "$result_file" << EOF
{
    "branch": "main",
    "commit": "official-container",
    "scenario": "$scenario",
    "docs_type": "$DOCS_TYPE",
    "parallel_mode": "sequential",
    "parallel_workers": "N/A",
    "timestamp": "$TIMESTAMP",
    "runs": $RUNS,
    "metrics": {
        "wall_time": {
            "avg_seconds": $time_avg,
            "min_seconds": $time_min,
            "max_seconds": $time_max
        },
        "cpu_time": {
            "avg_seconds": $cpu_avg,
            "avg_percent": $cpu_pct_avg
        },
        "memory": {
            "avg_mb": $mem_avg,
            "min_mb": $mem_min,
            "max_mb": $mem_max,
            "source": "usr_bin_time"
        },
        "files_rendered": $files
    },
    "raw_wall_times_seconds": [$(IFS=,; echo "${times[*]}")],
    "raw_cpu_percents": [$(IFS=,; echo "${cpu_percents[*]}")],
    "raw_memories_mb": [$(IFS=,; echo "${memories[*]}")],
    "notes": "Memory from /usr/bin/time - may be less accurate than PHP profiling"
}
EOF

    log_success "Results saved: $result_file"

    echo ""
    echo "=== $scenario Summary (main branch) ==="
    echo "  Wall Time:  ${time_avg}s (min: ${time_min}s, max: ${time_max}s)"
    echo "  CPU:        ~${cpu_pct_avg}%"
    echo "  Memory:     ${mem_avg}MB peak (from /usr/bin/time)"
    echo "  Files:      $files"
    echo ""
}

# Main
echo "============================================"
echo "Benchmark: $SCENARIO (main branch)"
echo "Image:     $IMAGE"
echo "Docs:      $DOCS_TYPE ($DOCS_INPUT)"
echo "Runs:      $RUNS"
echo "============================================"
echo ""

if ! command -v jq &> /dev/null; then
    log_error "jq is required. Install with: apt-get install jq"
    exit 1
fi

mkdir -p "$RESULTS_DIR"

if [ "$SCENARIO" = "all" ]; then
    run_scenario cold
    run_scenario warm
else
    run_scenario "$SCENARIO"
fi

log_success "Main branch benchmarks complete!"
