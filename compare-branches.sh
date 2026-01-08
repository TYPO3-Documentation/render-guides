#!/bin/bash

CURRENT_BRANCH=$(git branch --show-current)

echo "=== Branch Performance Comparison ==="
echo ""

# Test main branch
echo ">>> Testing main branch..."
git checkout main 2>/dev/null
composer install --quiet 2>/dev/null
rm -rf var/cache /tmp/typo3-guides-ast-cache /tmp/bench-output

echo "Main - Cold cache:"
START=$(date +%s%N)
php bin/guides run Documentation --output /tmp/bench-output 2>&1 | tail -1
END=$(date +%s%N)
MAIN_COLD=$(( (END - START) / 1000000 ))
echo "  Time: ${MAIN_COLD} ms"

echo "Main - Warm cache:"
rm -rf /tmp/bench-output
START=$(date +%s%N)
php bin/guides run Documentation --output /tmp/bench-output 2>&1 | tail -1
END=$(date +%s%N)
MAIN_WARM=$(( (END - START) / 1000000 ))
echo "  Time: ${MAIN_WARM} ms"

# Test feature branch
echo ""
echo ">>> Testing feature/php-8.5-only branch..."
git checkout feature/php-8.5-only 2>/dev/null
composer install --quiet 2>/dev/null
rm -rf var/cache /tmp/typo3-guides-ast-cache /tmp/bench-output

echo "Feature - Cold cache:"
START=$(date +%s%N)
php bin/guides run Documentation --output /tmp/bench-output 2>&1 | tail -1
END=$(date +%s%N)
FEATURE_COLD=$(( (END - START) / 1000000 ))
echo "  Time: ${FEATURE_COLD} ms"

echo "Feature - Warm cache:"
rm -rf /tmp/bench-output
START=$(date +%s%N)
php bin/guides run Documentation --output /tmp/bench-output 2>&1 | tail -1
END=$(date +%s%N)
FEATURE_WARM=$(( (END - START) / 1000000 ))
echo "  Time: ${FEATURE_WARM} ms"

echo ""
echo "╔════════════════════════════════════════════════════════════╗"
echo "║                    COMPARISON SUMMARY                      ║"
echo "╠════════════════════════════════════════════════════════════╣"
printf "║ %-20s │ %12s │ %12s ║\n" "Scenario" "main" "feature"
echo "╠──────────────────────┼──────────────┼──────────────────────╣"
printf "║ %-20s │ %10d ms │ %10d ms ║\n" "Cold cache" "$MAIN_COLD" "$FEATURE_COLD"
printf "║ %-20s │ %10d ms │ %10d ms ║\n" "Warm cache" "$MAIN_WARM" "$FEATURE_WARM"
echo "╠════════════════════════════════════════════════════════════╣"
COLD_SPEEDUP=$(echo "scale=1; $MAIN_COLD / $FEATURE_COLD" | bc)
WARM_SPEEDUP=$(echo "scale=1; $MAIN_WARM / $FEATURE_WARM" | bc)
printf "║ Cold cache speedup: %sx faster                           ║\n" "$COLD_SPEEDUP"
printf "║ Warm cache speedup: %sx faster                           ║\n" "$WARM_SPEEDUP"
echo "╚════════════════════════════════════════════════════════════╝"

# Return to original branch
git checkout "$CURRENT_BRANCH" 2>/dev/null
