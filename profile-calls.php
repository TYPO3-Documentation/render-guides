<?php
/**
 * Count method calls during rendering using a simple approach
 */
require_once __DIR__ . '/vendor/autoload.php';

$testDir = __DIR__ . '/tests/Integration/tests-full/changelog';
$inputDir = $testDir . '/input';
$outputDir = sys_get_temp_dir() . '/profile-calls-' . time();

// Check what methods are most frequently called by analyzing the codebase
echo "=== Analyzing hot paths in phpdocumentor/guides ===\n\n";

// Count method definitions and usages
$srcDir = __DIR__ . '/vendor/phpdocumentor/guides/src';

$patterns = [
    'BaseUri::from' => 0,
    'Uri::new' => 0,
    '->render(' => 0,
    '->renderTemplate(' => 0,
    '->canonicalUrl(' => 0,
    '->absoluteUrl(' => 0,
    '->getDocumentEntry(' => 0,
    '->findDocumentEntry(' => 0,
    '->get(' => 0,
    '->supports(' => 0,
    '->reduceAnchor(' => 0,
    'filter_var(' => 0,
    'explode(' => 0,
    'implode(' => 0,
];

$iterator = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($srcDir, RecursiveDirectoryIterator::SKIP_DOTS)
);

foreach ($iterator as $file) {
    if ($file->getExtension() !== 'php') continue;

    $content = file_get_contents($file->getPathname());
    foreach ($patterns as $pattern => &$count) {
        $count += substr_count($content, $pattern);
    }
}

arsort($patterns);
echo "Call sites in source code:\n";
foreach ($patterns as $pattern => $count) {
    printf("  %-25s %d occurrences\n", $pattern, $count);
}

echo "\n=== Running actual render to measure time ===\n";

$start = hrtime(true);
shell_exec(sprintf(
    'php %s/bin/guides %s --output=%s 2>/dev/null',
    __DIR__,
    escapeshellarg($inputDir),
    escapeshellarg($outputDir)
));
$elapsed = (hrtime(true) - $start) / 1_000_000;

printf("\nTotal time: %.2f ms\n", $elapsed);

// Check timing breakdown
if (file_exists('/tmp/run-timings.json')) {
    $timings = json_decode(file_get_contents('/tmp/run-timings.json'), true);
    echo "\nTiming breakdown:\n";
    foreach ($timings as $key => $value) {
        if (is_array($value)) {
            echo "  $key:\n";
            foreach ($value as $k => $v) {
                printf("    %-15s %.2f ms\n", $k, $v);
            }
        } else {
            printf("  %-15s %.2f ms\n", $key, $value);
        }
    }
}

shell_exec("rm -rf " . escapeshellarg($outputDir));
