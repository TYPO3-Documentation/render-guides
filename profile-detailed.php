<?php
require_once __DIR__ . '/vendor/autoload.php';

use phpDocumentor\Guides\RenderContext;

// Hook into Twig rendering
$startTime = [];
$renderTimes = [];

// Patch TwigTemplateRenderer temporarily
$refClass = new ReflectionClass(\phpDocumentor\Guides\Twig\TwigTemplateRenderer::class);
echo "TwigTemplateRenderer methods:\n";
foreach ($refClass->getMethods() as $method) {
    echo "  - " . $method->getName() . "\n";
}

// Just run and time
$testDir = __DIR__ . '/tests/Integration/tests-full/changelog';
$inputDir = $testDir . '/input';
$outputDir = sys_get_temp_dir() . '/profile-output-' . time();

$command = sprintf(
    'php %s/bin/guides %s --output=%s 2>&1',
    __DIR__,
    escapeshellarg($inputDir),
    escapeshellarg($outputDir)
);

$start = hrtime(true);
$output = shell_exec($command);
$elapsed = (hrtime(true) - $start) / 1_000_000;

echo "\nTotal render time: " . round($elapsed, 2) . "ms\n";
echo "\nPhase timings:\n";
$timings = json_decode(file_get_contents('/tmp/run-timings.json'), true);
print_r($timings);

shell_exec("rm -rf " . escapeshellarg($outputDir));
