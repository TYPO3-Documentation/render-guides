<?php

namespace T3Docs\GuidesExtension\Command;

use League\Tactician\CommandBus;
use Monolog\Handler\ErrorLogHandler;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use phpDocumentor\DevServer\ServerFactory;
use phpDocumentor\DevServer\Watcher\FileModifiedEvent;
use phpDocumentor\FileSystem\FlySystemAdapter;
use phpDocumentor\Guides\Cli\Command\ProgressBarSubscriber;
use phpDocumentor\Guides\Cli\Command\SettingsBuilder;
use phpDocumentor\Guides\Cli\DevServer\RerenderListener;
use phpDocumentor\Guides\Cli\Internal\RunCommand;
use phpDocumentor\Guides\Cli\Logger\SpyProcessor;
use phpDocumentor\Guides\Event\PostParseDocument;
use phpDocumentor\Guides\Nodes\DocumentNode;
use Psr\Log\LogLevel;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\ConsoleOutputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Process\Process;
use T3Docs\GuidesExtension\Compiler\Cache\ContentHasher;
use T3Docs\GuidesExtension\EventListener\IncrementalCacheListener;
use T3Docs\GuidesExtension\Settings\ParallelSettings;
use T3Docs\Typo3DocsTheme\Settings\Typo3DocsInputSettings;

final class RunDecorator extends Command
{
    private const string DEFAULT_OUTPUT_DIRECTORY = 'Documentation-GENERATED-temp';
    private const string DEFAULT_INPUT_DIRECTORY = 'Documentation';

    /**
     * @see https://regex101.com/r/UD4jUt/1
     */
    private const string LOCALIZATION_DIRECTORY_REGEX = '/Localization\.(([a-z]+)(_[a-z]+)?)$/imsU';

    private const array INDEX_FILE_NAMES = [
        'Index.rst' => 'rst',
        'index.rst' => 'rst',
        'Index.md' => 'md',
        'index.md' => 'md',
    ];
    private const array FALLBACK_FILE_NAMES = [
        'README.rst' => 'rst',
        'README.md' => 'md',
    ];

    public function __construct(
        private readonly Typo3DocsInputSettings $inputSettings,
        private readonly SettingsBuilder $settingsBuilder,
        private readonly CommandBus $commandBus,
        private readonly EventDispatcher $eventDispatcher,
        private readonly Logger $logger,
        private readonly ProgressBarSubscriber $progressBarSubscriber,
        private readonly ContentHasher $contentHasher,
        private readonly IncrementalCacheListener $cacheListener,
        private readonly ParallelSettings $parallelSettings,
    ) {
        parent::__construct('run');
    }

    protected function configure(): void
    {
        $this->settingsBuilder->configureCommand($this);

        $this->addOption(
            'log-path',
            null,
            InputOption::VALUE_REQUIRED,
            'Write rendering log to this path',
        );
        $this->addOption(
            'fail-on-log',
            null,
            InputOption::VALUE_NONE,
            'If set, returns a non-zero exit code as soon as any warnings/errors occur',
        );

        $this->addOption(
            'fail-on-error',
            null,
            InputOption::VALUE_NONE,
            'If set, returns a non-zero exit code as soon as any errors occur',
        );

        $this->addOption(
            'progress',
            null,
            InputOption::VALUE_NEGATABLE,
            'Whether to show a progress bar',
            true,
        );

        $this->addOption(
            'localization',
            null,
            InputArgument::OPTIONAL,
            'Render a specific localization (for example "de_DE", "ru_RU", ...)',
        );

        // This option is evaluated in the PostProjectNodeCreated event in packages/typo3-docs-theme/src/EventListeners/AddThemeSettingsToProjectNode.php
        $this->addOption(
            'minimal-test',
            null,
            InputOption::VALUE_NONE,
            'Apply preset for minimal testing (format=singlepage)',
        );

        $this->addOption(
            'watch',
            null,
            InputOption::VALUE_NONE,
            'Watch the input directory and re-render on changes (requires inotify extension)',
        );

        $this->addOption(
            'host',
            null,
            InputOption::VALUE_REQUIRED,
            'The host to bind the dev server to',
            'localhost'
        );

        $this->addOption(
            'port',
            null,
            InputOption::VALUE_REQUIRED,
            'The port to bind the dev server to',
            '1337'
        );

        $this->addOption(
            'parallel-workers',
            null,
            InputOption::VALUE_REQUIRED,
            'Number of parallel worker processes for rendering (0 = auto-detect, -1 = disable)',
            '0'
        );

        $this->addOption(
            'render-batch',
            null,
            InputOption::VALUE_REQUIRED,
            'Internal: Comma-separated list of document paths to render (used by worker processes)',
        );

        $this->addOption(
            'worker-id',
            null,
            InputOption::VALUE_REQUIRED,
            'Internal: Worker process identifier (used by worker processes)',
        );
    }


    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $options = [];
        foreach ($input->getOptions() as $option => $value) {
            if ($value === null) {
                continue;
            }

            $options['--' . $option] = $value;
        }

        $arguments = $input->getArguments();
        $guessedInput = [];
        if ($arguments['input'] === null && $arguments['command'] !== 'run' && is_string($arguments['command'])) {
            $guessedInput = $this->guessInput($arguments['command'], $output, false);
            $input->setArgument('input', $guessedInput['input']);
            $input->setOption('input-format', $guessedInput['--input-format'] ?? null);
        } elseif ($arguments['input'] === null) {
            $guessedInput = $this->guessInput(self::DEFAULT_INPUT_DIRECTORY, $output, false);
            $input->setArgument('input', $guessedInput['input']);
            $input->setOption('input-format', $guessedInput['--input-format'] ?? null);
        }

        if (!isset($options['--output'])) {
            $input->setOption('output', getcwd() . '/' . self::DEFAULT_OUTPUT_DIRECTORY);
        }

        // Propagate all input settings to be used within events
        // through the Typo3DocsInputSettings singleton.
        $this->inputSettings->setInput($input);

        // Configure parallel processing based on CLI option
        // -1 = disabled (truly sequential), 0 = auto-detect, N = explicit worker count
        $parallelWorkersOption = $input->getOption('parallel-workers');
        if (is_numeric($parallelWorkersOption)) {
            $this->parallelSettings->setWorkerCount((int) $parallelWorkersOption);
            if ($output->isVerbose()) {
                $output->writeln(sprintf(
                    '<info>Parallel workers: %s</info>',
                    (int) $parallelWorkersOption === -1 ? 'disabled (sequential)'
                        : ((int) $parallelWorkersOption === 0 ? 'auto-detect' : (int) $parallelWorkersOption)
                ));
            }
        }

        // Check for worker subprocess mode (--render-batch option)
        $renderBatch = $input->getOption('render-batch');
        $workerId = $input->getOption('worker-id');
        if (is_string($renderBatch) && $renderBatch !== '') {
            $batchDocuments = array_filter(explode(',', $renderBatch), static fn(string $s): bool => $s !== '');
            $workerIdInt = is_numeric($workerId) ? (int) $workerId : 0;

            // Configure the cache listener for worker mode
            $this->cacheListener->setBatchFilter($batchDocuments, $workerIdInt);

            if ($output->isVerbose()) {
                $output->writeln(sprintf(
                    '<info>Worker %d: Processing %d documents</info>',
                    $workerIdInt,
                    count($batchDocuments)
                ));
            }
        }

        if ($output->isDebug()) {
            $readableOutput = "<info>Options:</info>\n";
            $readableOutput .= print_r($input->getOptions(), true);

            $readableOutput .= "<info>Arguments:</info>\n";
            $readableOutput .= print_r($input->getArguments(), true);

            $readableOutput .= "<info>Guessed Input:</info>\n";
            $readableOutput .= print_r($guessedInput, true);

            $output->writeln(sprintf("<info>DEBUG</info> Using parameters:\n%s", $readableOutput));
        }

        $baseExecution = $this->internalRun($input, $output);

        // When a localization is being rendered or we're in worker mode,
        // no other sub-localizations are allowed, the execution will end here.
        if ($baseExecution !== Command::SUCCESS
            || $input->getParameterOption('--localization')
            || $this->cacheListener->isWorkerMode()
        ) {
            return $baseExecution;
        }

        return $this->renderLocalizations($input, $output);
    }

    /**
     * Localization inside the Documentation directories need to be handled as
     * distinct renderings with their own guides.xml inputs. We render and run it with
     * all the same parameters, but replace the input/output/config to their own
     * paths. This will then perform a separate rendering from e.g.
     * `Documentation/Localization.ru_RU/Index.rst` to
     * `Documentation-GENERATED-temp/Localization.ru_RU/Index.html`.
     *
     * This is performed via symfony process calls to the render guides.
     * Localizations that need rendering are run in parallel for better performance.
     */
    public function renderLocalizations(InputInterface $input, OutputInterface $output): int
    {
        // Retrieve the original input values of the command
        $baseInputDirectives = [
            'input-file' => ($input->hasArgument('input') ? $input->getArgument('input') : false),
            'output' => ($input->hasOption('output') ? $input->getOption('output') : false),
            'config' => ($input->hasOption('config') ? $input->getOption('config') : false),
        ];

        // Check if the main input directory is set, else no localization is needed
        $path = $baseInputDirectives['input-file'];
        if (!is_string($path)) {
            return Command::SUCCESS;
        }
        $fullResourcesPath = realpath($path);
        if ($fullResourcesPath === false) {
            // No localizations available, this is fine.
            return Command::SUCCESS;
        }

        // Iterate the main input directory for directories matching Localization.xx_YY
        $finder = new Finder();
        $finder
            ->directories()
            ->in($fullResourcesPath)
            ->depth(0)
            ->name(self::LOCALIZATION_DIRECTORY_REGEX);

        // Collect localizations that need rendering (can't be skipped)
        /** @var array<string, array{process: Process, localization: string}> $runningProcesses */
        $runningProcesses = [];

        foreach ($finder as $directory) {
            $localization = $directory->getRelativePathname();
            $process = $this->prepareLocalizationProcess($localization, $baseInputDirectives, $input, $output);

            if ($process === null) {
                // Skipped or no entrypoint
                continue;
            }

            // Start process in background
            $process->start();
            $runningProcesses[$localization] = ['process' => $process, 'localization' => $localization];
            $output->writeln(sprintf('<info>Started parallel render: %s</info>', $localization));
        }

        if ($runningProcesses === []) {
            return Command::SUCCESS;
        }

        $output->writeln(sprintf('<info>Waiting for %d localization(s) to complete...</info>', count($runningProcesses)));

        // Wait for all processes to complete
        $hasErrors = false;
        foreach ($runningProcesses as $data) {
            $process = $data['process'];
            $localization = $data['localization'];

            $process->wait(function ($type, string|iterable $buffer) use ($output, $localization, &$hasErrors): void {
                if ($type === Process::ERR) {
                    $output->write(sprintf('<error>[%s] %s</error>', $localization, $buffer));
                    $hasErrors = true;
                } else {
                    $output->write($buffer);
                }
            });

            if (!$process->isSuccessful()) {
                $output->writeln(sprintf('<error>Localization %s failed</error>', $localization));
                $hasErrors = true;
            }
        }

        return $hasErrors ? Command::FAILURE : Command::SUCCESS;
    }

    /**
     * Prepare a Process for rendering a single localization.
     * Returns null if the localization should be skipped.
     *
     * @param array<string, mixed> $baseInputDirectives
     */
    private function prepareLocalizationProcess(string $availableLocalization, array $baseInputDirectives, InputInterface $input, OutputInterface $output): ?Process
    {
        $localInputDirectives = [];
        foreach ($baseInputDirectives as $baseInputDirectiveKey => $baseInputDirectiveValue) {
            $localInputDirectives[$baseInputDirectiveKey] = $baseInputDirectiveValue . DIRECTORY_SEPARATOR . $availableLocalization;
        }
        $output->writeln(sprintf('<info>Checking %s ...</info>', $availableLocalization));

        $guessInput = $this->guessInput($localInputDirectives['input-file'], $output, true);
        if ($guessInput === []) {
            $output->writeln('<info>Skipping, no entrypoint for localization found.</info>');
            return null;
        }

        // Check if localization can be skipped (nothing changed)
        $inputDir = $guessInput['input'] ?? $localInputDirectives['input-file'];
        $outputDir = $localInputDirectives['output'];
        if ($this->canSkipLocalization($inputDir, $outputDir, $output)) {
            $output->writeln(sprintf('<info>Skipping %s - no changes detected</info>', $availableLocalization));
            return null;
        }

        // Build process arguments (don't modify input object as we're running in parallel)
        $processArguments = $this->buildLocalizationProcessArguments(
            $guessInput,
            $localInputDirectives,
            $availableLocalization,
            $input
        );

        $process = new Process($processArguments);
        $output->writeln(sprintf('<comment>SUB-PROCESS:</comment> %s', $process->getCommandLine()));

        return $process;
    }

    /**
     * Build the process arguments for a localization render.
     *
     * @param array<string, string> $guessInput
     * @param array<string, mixed> $localInputDirectives
     * @return list<string>
     */
    private function buildLocalizationProcessArguments(array $guessInput, array $localInputDirectives, string $localization, InputInterface $input): array
    {
        $options = $input->getOptions();
        $phpSelf = is_string($_SERVER['PHP_SELF'] ?? null) ? $_SERVER['PHP_SELF'] : 'vendor/bin/guides';

        /** @var list<string> $shellCommands */
        $shellCommands = ['env', 'php', $phpSelf];

        foreach ($options as $option => $value) {
            // Skip options we'll override
            if (in_array($option, ['output', 'input-format', 'config', 'localization', 'progress'], true)) {
                continue;
            }
            if (is_bool($value) && $value) {
                $shellCommands[] = "--$option";
            } elseif (is_string($value)) {
                $shellCommands[] = "--$option=" . $value;
            }
        }

        // Add the localization-specific options
        $outputDir = $localInputDirectives['output'] ?? '';
        $shellCommands[] = '--output=' . (is_string($outputDir) ? $outputDir : '');
        $shellCommands[] = '--input-format=' . ($guessInput['--input-format'] ?? 'rst');

        // Only add config if the directory exists
        $configDir = $localInputDirectives['config'] ?? null;
        if (is_string($configDir) && is_dir($configDir)) {
            $shellCommands[] = '--config=' . $configDir;
        }

        $shellCommands[] = '--localization=' . $localization;

        // Localizations are rendered as a sub-process. There the progress bar
        // disturbs the output that is returned. We only want normal and error output then.
        $shellCommands[] = '--no-progress';

        // Add command name (e.g., 'run') as positional argument
        $command = $input->getArgument('command');
        if (is_string($command) && $command !== 'run') {
            $shellCommands[] = $command;
        }

        // Add input directory as the final positional argument
        $inputDir = $guessInput['input'] ?? ($localInputDirectives['input-file'] ?? '');
        $shellCommands[] = is_string($inputDir) ? $inputDir : '';

        return $shellCommands;
    }

    /** @return array<string, string> */
    private function guessInput(string $inputBaseDirectory, OutputInterface $output, bool $isAbsoluteDirectory = false): array
    {
        $currentDirectory = getcwd();
        if ($currentDirectory === false) {
            if ($output->isDebug()) {
                $output->writeln('<info>DEBUG</info> Could not fetch current working directory.');
            }

            return [];
        }

        if ($isAbsoluteDirectory) {
            // Directory is already fully passed, and not a sub-directory (i.e. for localizations)
            $inputDirectory = $inputBaseDirectory;
        } else {
            // Directory needs to be checked within our working space (i.e. /project in container)
            $inputDirectory = $currentDirectory . DIRECTORY_SEPARATOR . $inputBaseDirectory;
        }

        if (is_dir($inputDirectory)) {
            if ($output->isDebug()) {
                $output->writeln(sprintf('<info>INFO</info> Auto-detecting entry file in directory %s', $inputDirectory));
            }

            foreach (self::INDEX_FILE_NAMES as $filename => $extension) {
                if (file_exists($inputDirectory . DIRECTORY_SEPARATOR . $filename)) {
                    if ($output->isDebug()) {
                        $output->writeln(sprintf('<info>DEBUG</info> Using entrypoint %s', $filename));
                    }

                    return [
                        'input' => $inputDirectory,
                        '--input-format' => $extension,
                    ];
                }
            }
        } elseif ($output->isVerbose()) {
            $output->writeln(sprintf('<info>DEBUG</info> Could not search for entry file in missing directory %s', $inputDirectory));
        }

        if ($output->isVerbose()) {
            $output->writeln('<info>INFO</info> Index documentation file not found, trying README.rst or README.md');
        }

        foreach (self::FALLBACK_FILE_NAMES as $filename => $extension) {
            if (file_exists($currentDirectory . DIRECTORY_SEPARATOR . $filename)) {
                if ($output->isVerbose()) {
                    $output->writeln(sprintf('<info>DEBUG</info> Using entrypoint %s', $filename));
                }

                return [
                    'input' => $currentDirectory,
                    '--input-file' => $currentDirectory . DIRECTORY_SEPARATOR . $filename,
                    '--input-format' => $extension,
                ];
            }
        }

        return [];
    }

    /**
     * Check if a localization can be skipped because nothing has changed.
     *
     * @param string $inputDir The localization input directory
     * @param string $outputDir The localization output directory
     * @return bool True if the localization can be skipped
     */
    private function canSkipLocalization(string $inputDir, string $outputDir, OutputInterface $output): bool
    {
        $metaPath = $outputDir . '/_build_meta.json';

        // Check if cache file exists
        if (!file_exists($metaPath)) {
            if ($output->isVerbose()) {
                $output->writeln('<comment>No cache found, full render needed</comment>');
            }
            return false;
        }

        // Load and parse cache
        $json = file_get_contents($metaPath);
        if ($json === false) {
            return false;
        }

        $data = json_decode($json, true);
        if (!is_array($data) || !isset($data['exports']) || !is_array($data['exports'])) {
            return false;
        }

        // Get all source files in the localization directory
        if (!is_dir($inputDir)) {
            return false;
        }

        $finder = new Finder();
        $finder->files()->in($inputDir)->name(['*.rst', '*.md']);

        /** @var array<string, array<string, mixed>> $exports */
        $exports = $data['exports'];
        $unchangedCount = 0;
        $totalCount = 0;

        foreach ($finder as $file) {
            $totalCount++;
            $relativePath = $file->getRelativePathname();
            // Remove extension to get docPath
            $docPath = preg_replace('/\.(rst|md)$/', '', $relativePath);
            if (!is_string($docPath)) {
                continue;
            }

            if (!isset($exports[$docPath]) || !is_array($exports[$docPath])) {
                // New file, needs rendering
                if ($output->isVerbose()) {
                    $output->writeln(sprintf('<comment>New file detected: %s</comment>', $docPath));
                }
                return false;
            }

            // Check content hash
            $currentHash = $this->contentHasher->hashFile($file->getRealPath());
            $cachedData = $exports[$docPath];
            $cachedHash = isset($cachedData['contentHash']) && is_string($cachedData['contentHash'])
                ? $cachedData['contentHash']
                : null;

            if ($currentHash !== $cachedHash) {
                if ($output->isVerbose()) {
                    $output->writeln(sprintf('<comment>File changed: %s</comment>', $docPath));
                }
                return false;
            }

            $unchangedCount++;
        }

        // Check for deleted files
        foreach (array_keys($exports) as $cachedDocPath) {
            $rstPath = $inputDir . '/' . $cachedDocPath . '.rst';
            $mdPath = $inputDir . '/' . $cachedDocPath . '.md';

            if (!file_exists($rstPath) && !file_exists($mdPath)) {
                if ($output->isVerbose()) {
                    $output->writeln(sprintf('<comment>File deleted: %s</comment>', $cachedDocPath));
                }
                return false;
            }
        }

        if ($output->isVerbose()) {
            $output->writeln(sprintf('<info>All %d files unchanged, skipping localization</info>', $unchangedCount));
        }

        return true;
    }

    private function internalRun(InputInterface $input, OutputInterface $output): int
    {
        $this->settingsBuilder->overrideWithInput($input);
        $projectNode = $this->settingsBuilder->createProjectNode();
        $settings = $this->settingsBuilder->getSettings();

        $logPath = $settings->getLogPath();
        if ($logPath === 'php://stder') {
            $this->logger->setHandlers([new ErrorLogHandler(ErrorLogHandler::OPERATING_SYSTEM, Logger::WARNING)]);
        } else {
            $this->logger->setHandlers([new StreamHandler($logPath . '/warning.log', Logger::WARNING), new StreamHandler($logPath . '/error.log', Logger::ERROR)]);
        }

        if ($settings->isFailOnError()) {
            $spyProcessor = new SpyProcessor($settings->getFailOnError() ?? LogLevel::WARNING);
            $this->logger->pushProcessor($spyProcessor);
        }

        if ($output instanceof ConsoleOutputInterface && $settings->isShowProgressBar()) {
            $this->progressBarSubscriber->subscribe($output, $this->eventDispatcher);
        }

        $watch = $input->getOption('watch');
        if ($watch) {
            $host = $input->getOption('host');
            $port = $input->getOption('port');

            if (!is_string($host)) {
                $output->writeln('<error>Invalid host provided for dev server.</error>');
                return Command::FAILURE;
            }

            if (!is_numeric($port)) {
                $output->writeln('<error>Invalid port provided for dev server.</error>');
                return Command::FAILURE;
            }

            $port = (int)$port;

            $files = FlySystemAdapter::createForPath($settings->getOutput());
            $sourceFileSystem = FlySystemAdapter::createForPath($settings->getInput());
            $serverFactory = new ServerFactory($this->logger, $this->eventDispatcher);
            $server = $serverFactory->createDevServer(
                $settings->getInput(),
                $files,
                $host,
                '0.0.0.0',
                $port,
                array_map(
                    fn($file): string => trim($file) . '.html',
                    explode(',', $settings->getIndexName())
                ),
            );

            $server->addListener(
                PostParseDocument::class,
                static function (PostParseDocument $event) use ($server): void {
                    $server->watch($event->getOriginalFileName());
                },
            );
        }

        /** @var DocumentNode[] $documents */
        $documents = $this->commandBus->handle(
            new RunCommand($settings, $projectNode, $input),
        );

        $outputFormats = $settings->getOutputFormats();
        $outputDir = $settings->getOutput();
        if ($output->isQuiet() === false) {
            $lastFormat = '';

            if (count($outputFormats) > 1) {
                $lastFormat = (count($outputFormats) > 2 ? ',' : '') . ' and ' . strtoupper(array_pop($outputFormats));
            }

            $formatsText = strtoupper(implode(', ', $outputFormats)) . $lastFormat;

            $output->writeln(
                'Successfully placed ' . (is_countable($documents) ? count($documents) : 0) . ' rendered ' . $formatsText . ' files into ' . $outputDir,
            );
        }

        if ($settings->isFailOnError() && $spyProcessor->hasBeenCalled()) {
            return Command::FAILURE;
        }

        if (!$watch) {
            return Command::SUCCESS;
        }

        $server->addListener(
            FileModifiedEvent::class,
            new RerenderListener(
                $output,
                $this->commandBus,
                $sourceFileSystem,
                $settings,
                $projectNode,
                $documents,
                $server
            ),
        );

        $output->writeln(
            sprintf(
                'Server running at http://%s:%d',
                $host,
                $port,
            ),
        );
        $output->writeln('Press Ctrl+C to stop the server');

        $server->run();

        return Command::SUCCESS;
    }
}
