#!/bin/bash
#
# Comprehensive Performance Benchmark for render-guides
# Tests cold, warm, partial-index, and partial-leaf scenarios
# across all documentation sets
#

set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_DIR="$(dirname "$SCRIPT_DIR")"
RESULTS_DIR="$SCRIPT_DIR/results"
TIMESTAMP=$(date +%Y%m%d_%H%M%S)
BRANCH=$(git rev-parse --abbrev-ref HEAD 2>/dev/null || echo "unknown")

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
CYAN='\033[0;36m'
NC='\033[0m'

log_info() { echo -e "${BLUE}[INFO]${NC} $1"; }
log_success() { echo -e "${GREEN}[OK]${NC} $1"; }
log_warn() { echo -e "${YELLOW}[WARN]${NC} $1"; }
log_header() { echo -e "\n${CYAN}════════════════════════════════════════${NC}"; echo -e "${CYAN}  $1${NC}"; echo -e "${CYAN}════════════════════════════════════════${NC}"; }

cd "$PROJECT_DIR"
mkdir -p "$RESULTS_DIR"

# Documentation sets configuration
declare -A DOCS_PATHS=(
    ["rendertest"]="Documentation-rendertest"
    ["changelog"]="benchmark/test-docs/TYPO3-Core-Changelog/typo3/sysext/core/Documentation"
    ["coreapi"]="benchmark/test-docs/TYPO3CMS-Reference-CoreApi/Documentation"
)

declare -A DOCS_NAMES=(
    ["rendertest"]="Rendertest"
    ["changelog"]="TYPO3 Core Changelog"
    ["coreapi"]="TYPO3 Core API"
)

# Leaf documents (deep in tree, few dependents)
declare -A LEAF_DOCS=(
    ["rendertest"]="Extensions/Management.rst"
    ["changelog"]="Changelog/12.4.x/Feature-101553-IntroduceBackendToolbarItemsGroupedByPriority.rst"
    ["coreapi"]="ApiOverview/Fal/Architecture/Index.rst"
)

RUNS="${1:-3}"
OUTPUT_BASE="/tmp/benchmark-output"

clean_caches() {
    rm -rf /tmp/typo3-guides-ast-cache
    rm -rf /tmp/typo3-guides-twig-cache
    rm -rf /tmp/typo3-guides-inventory-cache
}

run_render() {
    local input_dir=$1
    local output_dir=$2
    local start_time end_time

    start_time=$(date +%s.%N)

    php -d memory_limit=1024M \
        ./vendor/bin/guides \
        --no-progress \
        --output="$output_dir" \
        "$input_dir" > /dev/null 2>&1

    end_time=$(date +%s.%N)
    echo "scale=3; $end_time - $start_time" | bc
}

run_benchmark_set() {
    local doc_key=$1
    local doc_path="${DOCS_PATHS[$doc_key]}"
    local doc_name="${DOCS_NAMES[$doc_key]}"
    local leaf_doc="${LEAF_DOCS[$doc_key]}"
    local output_dir="$OUTPUT_BASE/$doc_key"

    log_header "$doc_name"

    local file_count=$(find "$doc_path" -name "*.rst" 2>/dev/null | wc -l | tr -d ' ')
    log_info "Files: $file_count RST documents"

    # Check if leaf doc exists
    if [[ ! -f "$doc_path/$leaf_doc" ]]; then
        log_warn "Leaf doc not found: $doc_path/$leaf_doc"
        # Find an alternative
        leaf_doc=$(find "$doc_path" -name "*.rst" -type f | grep -v Index | head -1 | sed "s|$doc_path/||")
        log_info "Using alternative leaf: $leaf_doc"
    fi

    local results=()

    # === COLD ===
    log_info "Running COLD benchmark ($RUNS runs)..."
    local cold_times=()
    for ((i=1; i<=RUNS; i++)); do
        clean_caches
        rm -rf "$output_dir"
        mkdir -p "$output_dir"
        time_val=$(run_render "$doc_path" "$output_dir")
        cold_times+=("$time_val")
        log_success "  Run $i: ${time_val}s"
    done
    local cold_avg=$(echo "${cold_times[@]}" | tr ' ' '\n' | awk '{sum+=$1} END {printf "%.3f", sum/NR}')

    # === WARM ===
    log_info "Running WARM benchmark ($RUNS runs)..."
    # Initial render to populate cache
    clean_caches
    rm -rf "$output_dir"
    mkdir -p "$output_dir"
    run_render "$doc_path" "$output_dir" > /dev/null

    local warm_times=()
    for ((i=1; i<=RUNS; i++)); do
        time_val=$(run_render "$doc_path" "$output_dir")
        warm_times+=("$time_val")
        log_success "  Run $i: ${time_val}s"
    done
    local warm_avg=$(echo "${warm_times[@]}" | tr ' ' '\n' | awk '{sum+=$1} END {printf "%.3f", sum/NR}')

    # === PARTIAL INDEX ===
    log_info "Running PARTIAL-INDEX benchmark ($RUNS runs)..."
    # Backup Index.rst
    cp "$doc_path/Index.rst" "$doc_path/Index.rst.bak"

    local partial_index_times=()
    for ((i=1; i<=RUNS; i++)); do
        # Modify Index.rst content
        echo ".. Benchmark run $i - $(date +%s)" >> "$doc_path/Index.rst"
        time_val=$(run_render "$doc_path" "$output_dir")
        partial_index_times+=("$time_val")
        log_success "  Run $i: ${time_val}s"
        # Restore for next run
        cp "$doc_path/Index.rst.bak" "$doc_path/Index.rst"
    done
    rm -f "$doc_path/Index.rst.bak"
    local partial_index_avg=$(echo "${partial_index_times[@]}" | tr ' ' '\n' | awk '{sum+=$1} END {printf "%.3f", sum/NR}')

    # === PARTIAL LEAF ===
    log_info "Running PARTIAL-LEAF benchmark ($RUNS runs)..."
    log_info "  Leaf doc: $leaf_doc"
    # Backup leaf doc
    cp "$doc_path/$leaf_doc" "$doc_path/$leaf_doc.bak"

    local partial_leaf_times=()
    for ((i=1; i<=RUNS; i++)); do
        # Modify leaf doc content
        echo ".. Benchmark run $i - $(date +%s)" >> "$doc_path/$leaf_doc"
        time_val=$(run_render "$doc_path" "$output_dir")
        partial_leaf_times+=("$time_val")
        log_success "  Run $i: ${time_val}s"
        # Restore for next run
        cp "$doc_path/$leaf_doc.bak" "$doc_path/$leaf_doc"
    done
    rm -f "$doc_path/$leaf_doc.bak"
    local partial_leaf_avg=$(echo "${partial_leaf_times[@]}" | tr ' ' '\n' | awk '{sum+=$1} END {printf "%.3f", sum/NR}')

    # Calculate improvements
    local warm_pct=$(echo "scale=1; (1 - $warm_avg / $cold_avg) * 100" | bc)
    local partial_index_pct=$(echo "scale=1; (1 - $partial_index_avg / $cold_avg) * 100" | bc)
    local partial_leaf_pct=$(echo "scale=1; (1 - $partial_leaf_avg / $cold_avg) * 100" | bc)

    # Output summary
    echo ""
    log_success "=== $doc_name Results ==="
    echo "  Files:         $file_count"
    echo "  Cold:          ${cold_avg}s"
    echo "  Warm:          ${warm_avg}s (-${warm_pct}%)"
    echo "  Partial Index: ${partial_index_avg}s (-${partial_index_pct}%)"
    echo "  Partial Leaf:  ${partial_leaf_avg}s (-${partial_leaf_pct}%)"

    # Save to JSON
    local json_file="$RESULTS_DIR/${BRANCH//\//_}_${doc_key}_full_${TIMESTAMP}.json"
    cat > "$json_file" << EOF
{
    "branch": "$BRANCH",
    "timestamp": "$TIMESTAMP",
    "documentation": "$doc_name",
    "file_count": $file_count,
    "runs": $RUNS,
    "results": {
        "cold": {
            "average": $cold_avg,
            "times": [$(IFS=,; echo "${cold_times[*]}")]
        },
        "warm": {
            "average": $warm_avg,
            "improvement_pct": $warm_pct,
            "times": [$(IFS=,; echo "${warm_times[*]}")]
        },
        "partial_index": {
            "average": $partial_index_avg,
            "improvement_pct": $partial_index_pct,
            "times": [$(IFS=,; echo "${partial_index_times[*]}")]
        },
        "partial_leaf": {
            "average": $partial_leaf_avg,
            "improvement_pct": $partial_leaf_pct,
            "leaf_doc": "$leaf_doc",
            "times": [$(IFS=,; echo "${partial_leaf_times[*]}")]
        }
    }
}
EOF
    log_info "Results saved to: $json_file"
}

# Main execution
log_header "Full Benchmark Suite"
log_info "Branch: $BRANCH"
log_info "Runs per scenario: $RUNS"

# Run benchmarks for each documentation set
for doc_key in rendertest coreapi changelog; do
    if [[ -d "${DOCS_PATHS[$doc_key]}" ]]; then
        run_benchmark_set "$doc_key"
    else
        log_warn "Skipping $doc_key - directory not found: ${DOCS_PATHS[$doc_key]}"
    fi
done

log_header "Benchmark Complete"
