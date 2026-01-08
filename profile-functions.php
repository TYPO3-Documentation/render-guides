<?php
/**
 * Profile specific functions during rendering
 */
require_once __DIR__ . '/vendor/autoload.php';

$testDir = __DIR__ . '/tests/Integration/tests-full/changelog';
$inputDir = $testDir . '/input';
$outputDir = sys_get_temp_dir() . '/profile-output-' . time();

// Run with tracing to capture function calls
$tracingCode = <<<'PHP'
<?php
$callCounts = [];
$callTimes = [];

function trackCall(string $class, string $method, float $startTime): void {
    global $callCounts, $callTimes;
    $key = "$class::$method";
    $callCounts[$key] = ($callCounts[$key] ?? 0) + 1;
    $callTimes[$key] = ($callTimes[$key] ?? 0.0) + (hrtime(true) - $startTime) / 1_000_000;
}

register_shutdown_function(function() {
    global $callCounts, $callTimes;
    arsort($callCounts);
    echo "\n=== Function Call Counts (Top 20) ===\n";
    $i = 0;
    foreach ($callCounts as $func => $count) {
        if (++$i > 20) break;
        $time = $callTimes[$func] ?? 0;
        printf("%-60s %6d calls  %8.2f ms\n", $func, $count, $time);
    }
});
PHP;

// Use XHProf-style profiling if available
if (extension_loaded('xhprof')) {
    echo "XHProf available - using for detailed profiling\n";
    xhprof_enable(XHPROF_FLAGS_CPU | XHPROF_FLAGS_MEMORY);

    // Run the guides command
    passthru(sprintf(
        'php %s/bin/guides %s --output=%s 2>&1',
        __DIR__,
        escapeshellarg($inputDir),
        escapeshellarg($outputDir)
    ));

    $data = xhprof_disable();

    // Sort by exclusive wall time
    uasort($data, fn($a, $b) => ($b['wt'] ?? 0) - ($a['wt'] ?? 0));

    echo "\n=== XHProf Top 30 Functions (by wall time) ===\n";
    printf("%-70s %10s %8s %8s\n", "Function", "Calls", "Wall ms", "CPU ms");
    echo str_repeat("-", 100) . "\n";
    $i = 0;
    foreach ($data as $func => $stats) {
        if (++$i > 30) break;
        printf("%-70s %10d %8.2f %8.2f\n",
            substr($func, 0, 70),
            $stats['ct'] ?? 0,
            ($stats['wt'] ?? 0) / 1000,
            ($stats['cpu'] ?? 0) / 1000
        );
    }
} else {
    echo "XHProf not available - using basic timing\n";

    // Just measure total time with different scenarios
    $scenarios = [
        'cold' => 'First run (cold cache)',
        'warm' => 'Second run (warm cache)',
    ];

    foreach ($scenarios as $key => $desc) {
        $start = hrtime(true);
        passthru(sprintf(
            'php %s/bin/guides %s --output=%s 2>/dev/null',
            __DIR__,
            escapeshellarg($inputDir),
            escapeshellarg($outputDir)
        ));
        $elapsed = (hrtime(true) - $start) / 1_000_000;
        echo "$desc: " . round($elapsed, 2) . "ms\n";

        if ($key === 'cold') {
            // Keep output for warm run
        }
    }
}

// Cleanup
passthru("rm -rf " . escapeshellarg($outputDir));
