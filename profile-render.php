<?php
/**
 * Performance profiling for render-guides
 */
$testDir = __DIR__ . '/tests/Integration/tests-full/changelog';
$inputDir = $testDir . '/input';
$outputDir = sys_get_temp_dir() . '/profile-output';

$iterations = (int)($argv[1] ?? 10);
$times = [];

echo "Running $iterations iterations...\n";

for ($i = 0; $i < $iterations; $i++) {
    // Clean output directory
    if (is_dir($outputDir)) {
        shell_exec("rm -rf " . escapeshellarg($outputDir));
    }

    $start = hrtime(true);
    $output = shell_exec(sprintf(
        'php %s/bin/guides %s --output=%s 2>/dev/null',
        __DIR__,
        escapeshellarg($inputDir),
        escapeshellarg($outputDir)
    ));
    $elapsed = (hrtime(true) - $start) / 1_000_000;
    $times[] = $elapsed;
    echo ".";
}

echo "\n\n";

// Cleanup
if (is_dir($outputDir)) {
    shell_exec("rm -rf " . escapeshellarg($outputDir));
}

sort($times);
// Remove outliers (highest and lowest)
if ($iterations > 4) {
    array_shift($times);
    array_pop($times);
}

$avg = array_sum($times) / count($times);
$min = min($times);
$max = max($times);

printf("Render times (ms): avg=%.2f, min=%.2f, max=%.2f\n", $avg, $min, $max);
