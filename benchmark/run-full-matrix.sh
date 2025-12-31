#!/bin/bash
#
# Run full benchmark matrix across all configurations
#
# Matrix:
#   Branches: main (official container), feature (local build)
#   Parallel modes (feature only): sequential, auto, 16
#   Scenarios: cold, warm
#   Docs: small, large, changelog
#
# Usage: ./benchmark/run-full-matrix.sh [docs-type]
#   docs-type: small (default), large, changelog, all
#

set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_DIR="$(dirname "$SCRIPT_DIR")"

DOCS_TYPE="${1:-small}"
RUNS=3

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
BLUE='\033[0;34m'
YELLOW='\033[1;33m'
CYAN='\033[0;36m'
NC='\033[0m'

log_section() { echo -e "\n${CYAN}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"; echo -e "${CYAN}$1${NC}"; echo -e "${CYAN}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}\n"; }
log_info() { echo -e "${BLUE}[INFO]${NC} $1"; }
log_success() { echo -e "${GREEN}[OK]${NC} $1"; }
log_warn() { echo -e "${YELLOW}[WARN]${NC} $1"; }

# Run benchmarks for a specific docs type
run_for_docs() {
    local docs=$1

    log_section "Benchmarking: $docs"

    # Feature branch - sequential (no parallelism)
    log_section "Feature Branch - Sequential (--parallel-workers=-1)"
    "$SCRIPT_DIR/benchmark-docker.sh" cold "$RUNS" "$docs" sequential
    "$SCRIPT_DIR/benchmark-docker.sh" warm "$RUNS" "$docs" sequential

    # Feature branch - auto parallelism (default)
    log_section "Feature Branch - Auto Parallelism (--parallel-workers=0)"
    "$SCRIPT_DIR/benchmark-docker.sh" cold "$RUNS" "$docs" auto
    "$SCRIPT_DIR/benchmark-docker.sh" warm "$RUNS" "$docs" auto

    # Feature branch - force 16 workers
    log_section "Feature Branch - 16 Workers (--parallel-workers=16)"
    "$SCRIPT_DIR/benchmark-docker.sh" cold "$RUNS" "$docs" 16
    "$SCRIPT_DIR/benchmark-docker.sh" warm "$RUNS" "$docs" 16

    # Main branch - using official container
    log_section "Main Branch (official container)"
    "$SCRIPT_DIR/benchmark-main.sh" cold "$RUNS" "$docs"
    "$SCRIPT_DIR/benchmark-main.sh" warm "$RUNS" "$docs"
}

# Main
echo ""
echo "╔══════════════════════════════════════════════════════════════╗"
echo "║           FULL BENCHMARK MATRIX                              ║"
echo "╠══════════════════════════════════════════════════════════════╣"
echo "║  Feature Branch Modes:                                       ║"
echo "║    - sequential (--parallel-workers=-1)                      ║"
echo "║    - auto       (--parallel-workers=0)                       ║"
echo "║    - 16 workers (--parallel-workers=16)                      ║"
echo "║                                                              ║"
echo "║  Main Branch: official TYPO3 container                       ║"
echo "║                                                              ║"
echo "║  Scenarios: cold, warm                                       ║"
echo "║  Runs per scenario: $RUNS                                        ║"
echo "╚══════════════════════════════════════════════════════════════╝"
echo ""

if [ "$DOCS_TYPE" = "all" ]; then
    run_for_docs small
    run_for_docs large
    run_for_docs changelog
else
    run_for_docs "$DOCS_TYPE"
fi

log_section "BENCHMARK MATRIX COMPLETE"
log_success "Results saved in: $SCRIPT_DIR/results/"
echo ""
echo "Result files:"
ls -la "$SCRIPT_DIR/results/"*.json 2>/dev/null | tail -20 || echo "  (no results found)"
