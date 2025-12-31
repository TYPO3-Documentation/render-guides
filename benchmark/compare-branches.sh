#!/bin/bash
#
# Compare benchmark results between branches
#
# Usage: ./benchmark/compare-branches.sh [main_branch] [feature_branch]
#
# This script:
# 1. Stashes current changes
# 2. Runs benchmarks on main branch
# 3. Runs benchmarks on feature branch
# 4. Generates comparison report
#

set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_DIR="$(dirname "$SCRIPT_DIR")"
RESULTS_DIR="$SCRIPT_DIR/results"

MAIN_BRANCH="${1:-main}"
FEATURE_BRANCH="${2:-$(git rev-parse --abbrev-ref HEAD)}"
RUNS=3

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
BOLD='\033[1m'
NC='\033[0m'

log_info() { echo -e "${BLUE}[INFO]${NC} $1"; }
log_success() { echo -e "${GREEN}[OK]${NC} $1"; }
log_warn() { echo -e "${YELLOW}[WARN]${NC} $1"; }
log_error() { echo -e "${RED}[ERROR]${NC} $1"; }

cd "$PROJECT_DIR"

echo -e "${BOLD}"
echo "╔════════════════════════════════════════════════════════════╗"
echo "║         render-guides Performance Comparison               ║"
echo "╚════════════════════════════════════════════════════════════╝"
echo -e "${NC}"

log_info "Comparing: $MAIN_BRANCH vs $FEATURE_BRANCH"
log_info "Runs per scenario: $RUNS"
echo ""

# Check for uncommitted changes
if ! git diff-index --quiet HEAD --; then
    log_warn "You have uncommitted changes. Stashing..."
    git stash push -m "benchmark-stash-$(date +%s)"
    STASHED=true
else
    STASHED=false
fi

# Store current branch
ORIGINAL_BRANCH=$(git rev-parse --abbrev-ref HEAD)

cleanup() {
    log_info "Cleaning up..."
    git checkout "$ORIGINAL_BRANCH" 2>/dev/null || true
    if [ "$STASHED" = true ]; then
        git stash pop 2>/dev/null || true
    fi
}
trap cleanup EXIT

# Run benchmarks on a branch
run_branch_benchmarks() {
    local branch=$1
    log_info "Switching to branch: $branch"
    git checkout "$branch" 2>/dev/null

    # Ensure dependencies are installed
    if [ ! -d "vendor" ] || [ "composer.lock" -nt "vendor" ]; then
        log_info "Installing dependencies..."
        composer install --quiet 2>/dev/null || true
    fi

    log_info "Running benchmarks on $branch..."
    for scenario in cold warm partial; do
        "$SCRIPT_DIR/run-benchmark.sh" "$scenario" "$RUNS"
    done
}

# Extract metric from JSON
get_metric() {
    local file=$1
    local metric=$2
    grep -o "\"$metric\": [0-9.]*" "$file" | head -1 | cut -d: -f2 | tr -d ' '
}

# Find latest result file for branch/scenario
find_result() {
    local branch=$1
    local scenario=$2
    local pattern="${branch//\//_}_${scenario}_"
    ls -t "$RESULTS_DIR"/${pattern}*.json 2>/dev/null | head -1
}

# Generate comparison report
generate_report() {
    log_info "Generating comparison report..."

    local report_file="$RESULTS_DIR/comparison_$(date +%Y%m%d_%H%M%S).md"

    cat > "$report_file" << EOF
# Performance Benchmark Results

**Date:** $(date '+%Y-%m-%d %H:%M:%S')
**Main Branch:** $MAIN_BRANCH
**Feature Branch:** $FEATURE_BRANCH
**Test Project:** Documentation-rendertest (94 RST files)
**Runs per scenario:** $RUNS

## Summary

| Scenario | $MAIN_BRANCH | $FEATURE_BRANCH | Improvement |
|----------|--------------|-----------------|-------------|
EOF

    for scenario in cold warm partial; do
        local main_file=$(find_result "$MAIN_BRANCH" "$scenario")
        local feature_file=$(find_result "$FEATURE_BRANCH" "$scenario")

        if [ -n "$main_file" ] && [ -n "$feature_file" ]; then
            local main_avg=$(get_metric "$main_file" "avg_seconds")
            local feature_avg=$(get_metric "$feature_file" "avg_seconds")

            if [ -n "$main_avg" ] && [ -n "$feature_avg" ]; then
                local improvement=$(echo "scale=1; (1 - $feature_avg / $main_avg) * 100" | bc)
                local sign=""
                if (( $(echo "$improvement > 0" | bc -l) )); then
                    sign="+"
                fi
                echo "| $scenario | ${main_avg}s | ${feature_avg}s | ${sign}${improvement}% |" >> "$report_file"
            fi
        else
            echo "| $scenario | N/A | N/A | N/A |" >> "$report_file"
        fi
    done

    cat >> "$report_file" << 'EOF'

## Scenario Descriptions

- **cold**: Fresh render with no caches (baseline)
- **warm**: Re-render with all caches populated, no file changes
- **partial**: One file modified, re-render (simulates typical edit workflow)

## Key Metrics

### Warm Render (No Changes)
This scenario shows the maximum benefit of incremental rendering.
With no file changes, the feature branch should skip rendering entirely.

### Partial Change
This scenario simulates a typical development workflow where one file
is edited and the documentation is rebuilt. The feature branch should
only re-render affected files and their dependents.

## Raw Data

See individual JSON files in `benchmark/results/` for detailed metrics.
EOF

    echo ""
    log_success "Report saved to: $report_file"
    echo ""
    echo -e "${BOLD}=== Quick Summary ===${NC}"
    cat "$report_file" | grep -A20 "## Summary" | head -10
}

# Main execution
mkdir -p "$RESULTS_DIR"

# Run benchmarks on both branches
run_branch_benchmarks "$MAIN_BRANCH"
run_branch_benchmarks "$FEATURE_BRANCH"

# Generate comparison
generate_report

log_success "Benchmark comparison complete!"
