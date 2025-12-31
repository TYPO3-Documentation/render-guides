#!/bin/bash
#
# Generate markdown comparison report from benchmark results
#
# Usage: ./benchmark/generate-report.sh [main-branch] [feature-branch] [docs-type]
#
# Outputs markdown suitable for PR descriptions
#

set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
RESULTS_DIR="$SCRIPT_DIR/results"

MAIN_BRANCH="${1:-main}"
FEATURE_BRANCH="${2:-$(cd "$(dirname "$SCRIPT_DIR")" && git rev-parse --abbrev-ref HEAD 2>/dev/null | sed 's/\//_/g' || echo "unknown")}"
DOCS_TYPE="${3:-small}"

# Find latest result file for branch/scenario/docs
find_result() {
    local branch=$1
    local scenario=$2
    local docs=$3
    local pattern="${branch}_${scenario}_${docs}_"
    ls -t "$RESULTS_DIR"/${pattern}*.json 2>/dev/null | head -1
}

# Extract metric from JSON
get_metric() {
    local file=$1
    local path=$2
    if [ -f "$file" ]; then
        jq -r "$path" "$file" 2>/dev/null || echo "N/A"
    else
        echo "N/A"
    fi
}

# Calculate percentage change (positive = improvement)
calc_improvement() {
    local main_val=$1
    local feature_val=$2
    if [ "$main_val" = "N/A" ] || [ "$feature_val" = "N/A" ]; then
        echo "N/A"
        return
    fi
    if [ "$main_val" = "0" ]; then
        echo "N/A"
        return
    fi
    local improvement=$(echo "scale=1; (1 - $feature_val / $main_val) * 100" | bc 2>/dev/null || echo "0")
    # Format with sign
    if (( $(echo "$improvement >= 0" | bc -l) )); then
        echo "+${improvement}%"
    else
        echo "${improvement}%"
    fi
}

# Format with emoji indicator
format_improvement() {
    local improvement=$1
    if [ "$improvement" = "N/A" ]; then
        echo "-"
        return
    fi
    local value="${improvement%\%}"
    value="${value#+}"
    if (( $(echo "$value > 20" | bc -l) )); then
        echo "$improvement :rocket:"
    elif (( $(echo "$value > 5" | bc -l) )); then
        echo "$improvement :white_check_mark:"
    elif (( $(echo "$value > 0" | bc -l) )); then
        echo "$improvement"
    elif (( $(echo "$value < -10" | bc -l) )); then
        echo "$improvement :warning:"
    else
        echo "$improvement"
    fi
}

# Generate report
generate_report() {
    local main_cold=$(find_result "$MAIN_BRANCH" cold "$DOCS_TYPE")
    local feature_cold=$(find_result "$FEATURE_BRANCH" cold "$DOCS_TYPE")

    local main_commit=$(get_metric "$main_cold" '.commit')
    local feature_commit=$(get_metric "$feature_cold" '.commit')
    local files=$(get_metric "$feature_cold" '.metrics.files_rendered')
    local runs=$(get_metric "$feature_cold" '.runs')

    cat << EOF
## Performance Benchmark Results

**Baseline:** \`$MAIN_BRANCH\` ($main_commit)
**Feature:** \`$FEATURE_BRANCH\` ($feature_commit)
**Test Dataset:** $DOCS_TYPE ($files files)

### Render Time

| Scenario | $MAIN_BRANCH | $FEATURE_BRANCH | Improvement |
|----------|--------------|-----------------|-------------|
EOF

    for scenario in cold warm partial; do
        local main_file=$(find_result "$MAIN_BRANCH" "$scenario" "$DOCS_TYPE")
        local feature_file=$(find_result "$FEATURE_BRANCH" "$scenario" "$DOCS_TYPE")

        local main_time=$(get_metric "$main_file" '.metrics.time.avg_seconds')
        local feature_time=$(get_metric "$feature_file" '.metrics.time.avg_seconds')
        local improvement=$(calc_improvement "$main_time" "$feature_time")
        local formatted=$(format_improvement "$improvement")

        if [ "$main_time" != "N/A" ]; then
            main_time="${main_time}s"
        fi
        if [ "$feature_time" != "N/A" ]; then
            feature_time="${feature_time}s"
        fi

        echo "| $scenario | $main_time | $feature_time | $formatted |"
    done

    cat << EOF

### Peak Memory Usage

| Scenario | $MAIN_BRANCH | $FEATURE_BRANCH | Improvement |
|----------|--------------|-----------------|-------------|
EOF

    for scenario in cold warm partial; do
        local main_file=$(find_result "$MAIN_BRANCH" "$scenario" "$DOCS_TYPE")
        local feature_file=$(find_result "$FEATURE_BRANCH" "$scenario" "$DOCS_TYPE")

        local main_mem=$(get_metric "$main_file" '.metrics.memory.avg_mb')
        local feature_mem=$(get_metric "$feature_file" '.metrics.memory.avg_mb')
        local improvement=$(calc_improvement "$main_mem" "$feature_mem")
        local formatted=$(format_improvement "$improvement")

        if [ "$main_mem" != "N/A" ]; then
            main_mem="${main_mem}MB"
        fi
        if [ "$feature_mem" != "N/A" ]; then
            feature_mem="${feature_mem}MB"
        fi

        echo "| $scenario | $main_mem | $feature_mem | $formatted |"
    done

    cat << EOF

### Scenario Descriptions

- **cold**: Fresh render with no cache (worst case)
- **warm**: Re-render with full cache, no file changes (best case for incremental)
- **partial**: One file modified, re-render (typical development workflow)

### Test Configuration

- Runs per scenario: $runs
- Docker container: PHP 8.5-cli-alpine with OPcache enabled

---
<details>
<summary>Raw benchmark data</summary>

**Main branch results:**
\`\`\`
$(ls -1 "$RESULTS_DIR"/${MAIN_BRANCH}_*_${DOCS_TYPE}_*.json 2>/dev/null | xargs -I {} basename {} 2>/dev/null | head -5 || echo "No results found")
\`\`\`

**Feature branch results:**
\`\`\`
$(ls -1 "$RESULTS_DIR"/${FEATURE_BRANCH}_*_${DOCS_TYPE}_*.json 2>/dev/null | xargs -I {} basename {} 2>/dev/null | head -5 || echo "No results found")
\`\`\`

</details>
EOF
}

# Ensure jq is available
if ! command -v jq &> /dev/null; then
    echo "Error: jq is required but not installed." >&2
    exit 1
fi

# Check if results exist
if [ ! -d "$RESULTS_DIR" ] || [ -z "$(ls -A "$RESULTS_DIR" 2>/dev/null)" ]; then
    echo "Error: No benchmark results found in $RESULTS_DIR" >&2
    echo "Run benchmarks first: ./benchmark/benchmark-docker.sh all 3 small" >&2
    exit 1
fi

generate_report
