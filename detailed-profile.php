<?php
declare(strict_types=1);

require __DIR__ . '/vendor/autoload.php';

use phpDocumentor\Guides\Cli\Application;
use phpDocumentor\Guides\Cli\DependencyInjection\ApplicationExtension;
use phpDocumentor\Guides\Cli\DependencyInjection\ContainerFactory;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;

// Track timing
$phases = [];

$start = hrtime(true);

$containerFactory = new ContainerFactory([new ApplicationExtension()]);
$container = $containerFactory->create(__DIR__ . '/vendor');

$phases['container'] = (hrtime(true) - $start) / 1e6;
$phaseStart = hrtime(true);

$application = $container->get(Application::class);
$command = $application->find('run');

$phases['app_init'] = (hrtime(true) - $phaseStart) / 1e6;

// Clean output directory
exec('rm -rf /tmp/docs-profile');

$input = new ArrayInput([
    'input' => 'tests/Integration/tests-full/menu-subpages/input',
    '--output' => '/tmp/docs-profile',
    '--config' => 'packages/typo3-docs-theme/resources/config',
    '--no-progress' => true,
]);
$output = new BufferedOutput();

// Run first time (cold)
$phaseStart = hrtime(true);
$command->run($input, $output);
$phases['cold_run'] = (hrtime(true) - $phaseStart) / 1e6;

// Run again (warm)
exec('rm -rf /tmp/docs-profile');
$phaseStart = hrtime(true);
$command->run($input, $output);
$phases['warm_run'] = (hrtime(true) - $phaseStart) / 1e6;

// Output
echo "=== Detailed Timing Breakdown ===\n";
foreach ($phases as $phase => $time) {
    printf("%-20s %8.2f ms\n", $phase . ':', $time);
}
printf("%-20s %8.2f MB\n", 'peak_memory:', memory_get_peak_usage() / 1024 / 1024);
