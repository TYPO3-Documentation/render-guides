<?php
/**
 * Detailed timing analysis - measure specific operations
 */
require_once __DIR__ . '/vendor/autoload.php';

use League\Uri\BaseUri;
use League\Uri\Uri;
use Symfony\Component\String\Slugger\AsciiSlugger;

// Test 1: BaseUri::from performance
$iterations = 10000;
$testUrls = ['index.html', '/path/to/file.html', '../relative/path.html', 'simple', 'path/with/multiple/segments.html#anchor'];

echo "=== Micro-benchmarks ($iterations iterations each) ===\n\n";

// BaseUri::from
$start = hrtime(true);
for ($i = 0; $i < $iterations; $i++) {
    foreach ($testUrls as $url) {
        BaseUri::from($url);
    }
}
$elapsed = (hrtime(true) - $start) / 1_000_000;
printf("BaseUri::from(): %.2f ms (%.4f ms/call)\n", $elapsed, $elapsed / ($iterations * count($testUrls)));

// Uri::new
$start = hrtime(true);
for ($i = 0; $i < $iterations; $i++) {
    foreach ($testUrls as $url) {
        Uri::new($url);
    }
}
$elapsed = (hrtime(true) - $start) / 1_000_000;
printf("Uri::new(): %.2f ms (%.4f ms/call)\n", $elapsed, $elapsed / ($iterations * count($testUrls)));

// AsciiSlugger
$slugger = new AsciiSlugger();
$testStrings = ['Hello World', 'Some-Title-Here', 'CamelCaseTitle', 'with_underscores', 'Mixed 123 Numbers'];
$start = hrtime(true);
for ($i = 0; $i < $iterations; $i++) {
    foreach ($testStrings as $str) {
        $slugger->slug($str)->toString();
    }
}
$elapsed = (hrtime(true) - $start) / 1_000_000;
printf("AsciiSlugger->slug(): %.2f ms (%.4f ms/call)\n", $elapsed, $elapsed / ($iterations * count($testStrings)));

// String operations
$start = hrtime(true);
for ($i = 0; $i < $iterations; $i++) {
    foreach ($testUrls as $url) {
        explode('/', $url);
        ltrim($url, '/');
        trim($url, '/');
    }
}
$elapsed = (hrtime(true) - $start) / 1_000_000;
printf("String ops (explode/ltrim/trim): %.2f ms (%.4f ms/call)\n", $elapsed, $elapsed / ($iterations * count($testUrls)));

// filter_var for URL validation
$start = hrtime(true);
for ($i = 0; $i < $iterations; $i++) {
    foreach ($testUrls as $url) {
        filter_var($url, FILTER_VALIDATE_URL);
        filter_var($url, FILTER_VALIDATE_EMAIL);
    }
}
$elapsed = (hrtime(true) - $start) / 1_000_000;
printf("filter_var (URL+EMAIL): %.2f ms (%.4f ms/call)\n", $elapsed, $elapsed / ($iterations * count($testUrls)));

echo "\n=== Twig Template Rendering Test ===\n";

// Load the actual Twig environment
$testDir = __DIR__ . '/tests/Integration/tests-full/changelog';
$inputDir = $testDir . '/input';
$outputDir = sys_get_temp_dir() . '/profile-twig-' . time();

// Run a single render and capture detailed timing
$command = sprintf(
    'php %s/bin/guides %s --output=%s 2>&1',
    __DIR__,
    escapeshellarg($inputDir),
    escapeshellarg($outputDir)
);

$start = hrtime(true);
shell_exec($command);
$totalTime = (hrtime(true) - $start) / 1_000_000;

echo "Total render time: " . round($totalTime, 2) . " ms\n";

// Check timing file
if (file_exists('/tmp/run-timings.json')) {
    $timings = json_decode(file_get_contents('/tmp/run-timings.json'), true);
    echo "\nPhase breakdown:\n";
    printf("  Parse:   %7.2f ms (%5.1f%%)\n", $timings['parse_ms'], ($timings['parse_ms'] / $totalTime) * 100);
    printf("  Compile: %7.2f ms (%5.1f%%)\n", $timings['compile_ms'], ($timings['compile_ms'] / $totalTime) * 100);
    printf("  Render:  %7.2f ms (%5.1f%%)\n", $timings['render_ms'], ($timings['render_ms'] / $totalTime) * 100);

    if (isset($timings['render_by_format'])) {
        echo "\nRender by format:\n";
        foreach ($timings['render_by_format'] as $format => $time) {
            printf("  %-12s %7.2f ms\n", $format . ':', $time);
        }
    }
}

shell_exec("rm -rf " . escapeshellarg($outputDir));
