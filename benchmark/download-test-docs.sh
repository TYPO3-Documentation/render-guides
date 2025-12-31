#!/bin/bash
#
# Download TYPO3 official documentation for benchmarking
#
# Usage: ./benchmark/download-test-docs.sh [repo-name]
#
# Available repositories:
#   TYPO3CMS-Reference-CoreApi       (~80MB, ~957 RST files)
#   TYPO3CMS-Reference-TCA           (~15MB, medium)
#   TYPO3CMS-Tutorial-GettingStarted (~5MB, small)
#   TYPO3-Core-Changelog             (~3666 RST files, extra large)
#

set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
TEST_DOCS_DIR="$SCRIPT_DIR/test-docs"

# Default to CoreApi as it's the largest/most representative
REPO_NAME="${1:-TYPO3CMS-Reference-CoreApi}"

# Special handling for TYPO3 Core Changelog (sparse checkout from monorepo)
if [ "$REPO_NAME" = "TYPO3-Core-Changelog" ]; then
    REPO_DIR="$TEST_DOCS_DIR/$REPO_NAME"
    REPO_URL="https://github.com/TYPO3/typo3.git"
    SPARSE_PATH="typo3/sysext/core/Documentation"
else
    REPO_URL="https://github.com/TYPO3-Documentation/${REPO_NAME}.git"
    REPO_DIR="$TEST_DOCS_DIR/$REPO_NAME"
fi

# Colors
GREEN='\033[0;32m'
BLUE='\033[0;34m'
NC='\033[0m'

log_info() { echo -e "${BLUE}[INFO]${NC} $1"; }
log_success() { echo -e "${GREEN}[OK]${NC} $1"; }

mkdir -p "$TEST_DOCS_DIR"

# Create .gitkeep
touch "$TEST_DOCS_DIR/.gitkeep"

# Special handling for TYPO3 Core Changelog (sparse checkout)
if [ "$REPO_NAME" = "TYPO3-Core-Changelog" ]; then
    if [ -d "$REPO_DIR/.git" ]; then
        log_info "Repository already exists, updating..."
        cd "$REPO_DIR"
        git fetch --quiet
        git reset --hard origin/main --quiet
        log_success "Updated $REPO_NAME"
    else
        log_info "Cloning $REPO_NAME with sparse checkout (this may take a while)..."
        git clone --depth=1 --filter=blob:none --sparse "$REPO_URL" "$REPO_DIR"
        cd "$REPO_DIR"
        git sparse-checkout set "$SPARSE_PATH"
        # Create symlink for consistent path structure
        mkdir -p "$REPO_DIR/Documentation"
        if [ ! -L "$REPO_DIR/Documentation/Changelog" ]; then
            ln -sf "../$SPARSE_PATH/Changelog" "$REPO_DIR/Documentation/Changelog"
        fi
        # Copy guides.xml if it exists, or create a minimal one
        if [ -f "$REPO_DIR/$SPARSE_PATH/guides.xml" ]; then
            cp "$REPO_DIR/$SPARSE_PATH/guides.xml" "$REPO_DIR/Documentation/"
        fi
        # Copy Index.rst
        if [ -f "$REPO_DIR/$SPARSE_PATH/Index.rst" ]; then
            cp "$REPO_DIR/$SPARSE_PATH/Index.rst" "$REPO_DIR/Documentation/"
        fi
        log_success "Cloned $REPO_NAME (sparse checkout)"
    fi
    DOC_PATH="$REPO_DIR/$SPARSE_PATH"
else
    if [ -d "$REPO_DIR/.git" ]; then
        log_info "Repository already exists, updating..."
        cd "$REPO_DIR"
        git fetch --quiet
        git reset --hard origin/main --quiet 2>/dev/null || git reset --hard origin/master --quiet
        log_success "Updated $REPO_NAME"
    else
        log_info "Cloning $REPO_NAME (this may take a while)..."
        git clone --depth=1 --single-branch "$REPO_URL" "$REPO_DIR"
        log_success "Cloned $REPO_NAME"
    fi
    DOC_PATH="$REPO_DIR/Documentation"
fi

# Report stats
RST_COUNT=$(find "$DOC_PATH" -name "*.rst" 2>/dev/null | wc -l | tr -d ' ')
SIZE=$(du -sh "$REPO_DIR" | cut -f1)

log_success "Downloaded: $REPO_NAME"
echo "  - RST files: $RST_COUNT"
echo "  - Size: $SIZE"
echo "  - Path: $DOC_PATH"
