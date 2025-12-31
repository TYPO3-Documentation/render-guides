#!/bin/bash
#
# Performance Benchmark Runner for render-guides
#
# Usage: ./benchmark/run-benchmark.sh [scenario] [runs]
#
# Scenarios:
#   cold    - Fresh render, no cache
#   warm    - Re-render with full cache
#   partial - Modify one file, re-render
#
# Examples:
#   ./benchmark/run-benchmark.sh cold 3
#   ./benchmark/run-benchmark.sh warm 5
#

set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_DIR="$(dirname "$SCRIPT_DIR")"
RESULTS_DIR="$SCRIPT_DIR/results"

# Configuration
DOCS_INPUT="Documentation-rendertest"
DOCS_OUTPUT="/tmp/benchmark-output"
SCENARIO="${1:-cold}"
RUNS="${2:-3}"
BRANCH=$(git rev-parse --abbrev-ref HEAD 2>/dev/null || echo "unknown")
TIMESTAMP=$(date +%Y%m%d_%H%M%S)

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

log_info() { echo -e "${BLUE}[INFO]${NC} $1"; }
log_success() { echo -e "${GREEN}[OK]${NC} $1"; }
log_warn() { echo -e "${YELLOW}[WARN]${NC} $1"; }

# Ensure we're in project root
cd "$PROJECT_DIR"

# Validate scenario
case "$SCENARIO" in
    cold|warm|partial|config)
        log_info "Running scenario: $SCENARIO"
        ;;
    *)
        echo "Usage: $0 [cold|warm|partial|config] [runs]"
        exit 1
        ;;
esac

# Clean output directory
clean_output() {
    rm -rf "$DOCS_OUTPUT"
    mkdir -p "$DOCS_OUTPUT"
}

# Clean all caches
clean_caches() {
    rm -rf /tmp/typo3-guides-ast-cache
    rm -rf /tmp/typo3-guides-twig-cache
    rm -rf /tmp/typo3-guides-inventory-cache
    rm -rf "$DOCS_OUTPUT"
}

# Run single render and capture metrics
run_render() {
    local run_num=$1
    local start_time end_time elapsed
    local mem_before mem_after mem_peak

    start_time=$(date +%s.%N)

    # Run the render command, suppress output
    php -d memory_limit=512M \
        ./vendor/bin/guides \
        --no-progress \
        --output="$DOCS_OUTPUT" \
        --config="$DOCS_INPUT" \
        "$DOCS_INPUT" > /dev/null 2>&1

    end_time=$(date +%s.%N)
    elapsed=$(echo "scale=3; $end_time - $start_time" | bc)

    # Count files in output
    local file_count
    file_count=$(find "$DOCS_OUTPUT" -name "*.html" 2>/dev/null | wc -l | tr -d ' ')

    echo "$elapsed|$file_count"
}

# Run benchmark for a scenario
run_scenario() {
    local scenario=$1
    local times=()
    local files=0

    log_info "Scenario: $scenario, Runs: $RUNS"

    case "$scenario" in
        cold)
            # Each run starts fresh
            for ((i=1; i<=RUNS; i++)); do
                log_info "Run $i/$RUNS (cold)..."
                clean_caches
                clean_output
                result=$(run_render $i)
                time_val=$(echo "$result" | cut -d'|' -f1)
                files=$(echo "$result" | cut -d'|' -f2)
                times+=("$time_val")
                log_success "  Time: ${time_val}s, Files: $files"
            done
            ;;
        warm)
            # First run populates cache, subsequent runs use it
            log_info "Initial render to populate cache..."
            clean_caches
            clean_output
            run_render 0 > /dev/null

            for ((i=1; i<=RUNS; i++)); do
                log_info "Run $i/$RUNS (warm)..."
                # Don't clean caches, but clean output to force re-render decision
                result=$(run_render $i)
                time_val=$(echo "$result" | cut -d'|' -f1)
                files=$(echo "$result" | cut -d'|' -f2)
                times+=("$time_val")
                log_success "  Time: ${time_val}s, Files: $files"
            done
            ;;
        partial)
            # Modify one file between runs
            log_info "Initial render..."
            clean_caches
            clean_output
            run_render 0 > /dev/null

            for ((i=1; i<=RUNS; i++)); do
                log_info "Run $i/$RUNS (partial - modifying Index.rst)..."
                # Touch a file to trigger partial re-render
                touch "$DOCS_INPUT/Index.rst"
                sleep 0.1  # Ensure mtime changes
                result=$(run_render $i)
                time_val=$(echo "$result" | cut -d'|' -f1)
                files=$(echo "$result" | cut -d'|' -f2)
                times+=("$time_val")
                log_success "  Time: ${time_val}s, Files: $files"
            done
            ;;
        config)
            # Modify config to trigger full re-render
            log_info "Initial render..."
            clean_caches
            clean_output
            run_render 0 > /dev/null

            for ((i=1; i<=RUNS; i++)); do
                log_info "Run $i/$RUNS (config change)..."
                touch "$DOCS_INPUT/guides.xml"
                sleep 0.1
                result=$(run_render $i)
                time_val=$(echo "$result" | cut -d'|' -f1)
                files=$(echo "$result" | cut -d'|' -f2)
                times+=("$time_val")
                log_success "  Time: ${time_val}s, Files: $files"
            done
            ;;
    esac

    # Calculate statistics
    local sum=0
    local min=${times[0]}
    local max=${times[0]}

    for t in "${times[@]}"; do
        sum=$(echo "scale=3; $sum + $t" | bc)
        if [ $(echo "$t < $min" | bc -l) -eq 1 ]; then min=$t; fi
        if [ $(echo "$t > $max" | bc -l) -eq 1 ]; then max=$t; fi
    done

    local avg=$(echo "scale=3; $sum / ${#times[@]}" | bc)

    # Output results
    echo ""
    log_success "=== Results for $scenario ==="
    echo "  Runs:    $RUNS"
    echo "  Average: ${avg}s"
    echo "  Min:     ${min}s"
    echo "  Max:     ${max}s"
    echo "  Files:   $files"
    echo ""

    # Save to JSON
    local result_file="$RESULTS_DIR/${BRANCH//\//_}_${scenario}_${TIMESTAMP}.json"
    cat > "$result_file" << EOF
{
    "branch": "$BRANCH",
    "scenario": "$scenario",
    "timestamp": "$TIMESTAMP",
    "project": "$DOCS_INPUT",
    "runs": $RUNS,
    "metrics": {
        "avg_seconds": $avg,
        "min_seconds": $min,
        "max_seconds": $max,
        "files_rendered": $files
    },
    "raw_times": [$(IFS=,; echo "${times[*]}")]
}
EOF
    log_info "Results saved to: $result_file"
}

# Main
mkdir -p "$RESULTS_DIR"
run_scenario "$SCENARIO"
