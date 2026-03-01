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
use T3Docs\Typo3DocsTheme\Settings\Typo3DocsInputSettings;

final class RunDecorator extends Command
{
    private const DEFAULT_OUTPUT_DIRECTORY = 'Documentation-GENERATED-temp';
    private const DEFAULT_INPUT_DIRECTORY = 'Documentation';

    /**
     * @see https://regex101.com/r/UD4jUt/1
     */
    private const LOCALIZATION_DIRECTORY_REGEX = '/Localization\.(([a-z]+)(_[a-z]+)?)$/imsU';

    private const INDEX_FILE_NAMES = [
        'Index.rst' => 'rst',
        'index.rst' => 'rst',
        'Index.md' => 'md',
        'index.md' => 'md',
    ];
    private const FALLBACK_FILE_NAMES = [
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

        // When a localization is being rendered, no other sub-localizations
        // are allowed, the execution will end here.
        if ($baseExecution !== Command::SUCCESS || $input->getParameterOption('--localization')) {
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
     * This is performed via symfony process calls to the render guides
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

        foreach ($finder as $directory) {
            $singleLocalizationExecution = $this->renderSingleLocalization(
                $directory->getRelativePathname(),
                $baseInputDirectives,
                $input,
                $output
            );

            if ($singleLocalizationExecution !== Command::SUCCESS) {
                return $singleLocalizationExecution;
            }

        }

        return Command::SUCCESS;
    }

    /**
     * @param array<string, mixed> $baseInputDirectives
     */
    public function renderSingleLocalization(string $availableLocalization, array $baseInputDirectives, InputInterface $input, OutputInterface $output): int
    {
        $localInputDirectives = [];
        foreach ($baseInputDirectives as $baseInputDirectiveKey => $baseInputDirectiveValue) {
            if (!is_scalar($baseInputDirectiveValue)) {
                continue;
            }
            $localInputDirectives[$baseInputDirectiveKey] = $baseInputDirectiveValue . DIRECTORY_SEPARATOR . $availableLocalization;
        }
        $output->writeln(sprintf('<info>Trying to render %s ...</info>', $availableLocalization));

        $guessInput = $this->guessInput($localInputDirectives['input-file'], $output, true);
        if ($guessInput === []) {
            $output->writeln('<info>Skipping, no entrypoint for localization found.</info>');
            return Command::SUCCESS;
        }

        // Re-wire the command arguments to what we need for localization ...
        $input->setArgument('input', $guessInput['input']);
        $input->setOption('input-format', $guessInput['--input-format']);
        $input->setOption('output', $localInputDirectives['output']);
        $input->setOption('config', $localInputDirectives['config']);
        $input->setOption('localization', $availableLocalization);

        if ($output->isDebug()) {
            $readableOutput = "<info>baseInputDirectives:</info>\n";
            $readableOutput .= print_r($baseInputDirectives, true);
            $readableOutput .= "<info>localInputDirectives:</info>\n";
            $readableOutput .= print_r($localInputDirectives, true);
            $readableOutput .= "<info>Entry file:</info>\n";
            $readableOutput .= print_r($guessInput, true);
            $readableOutput .= "<info>Actual Arguments:</info>\n";
            $readableOutput .= print_r($input->getArguments(), true);
            $readableOutput .= "<info>Actual Options:</info>\n";
            $readableOutput .= print_r($input->getOptions(), true);
            $output->writeln(sprintf("<info>DEBUG</info> Using parameters:\n%s", $readableOutput));
        }

        $processArguments = array_merge(['env', 'php', $_SERVER['PHP_SELF']], $this->retrieveLocalizationArgumentsFromCurrentArguments($input));

        $process = new Process($processArguments);
        $output->writeln(sprintf('<info>SUB-PROCESS:</info> %s', $process->getCommandLine()));
        $hasErrors = false;
        $result = $process->run(function (string $type, string $buffer) use ($output, &$hasErrors): void {
            if ($type === Process::ERR) {
                $output->write('<error>' . $buffer . '</error>');
                $hasErrors = true;
            } else {
                $output->write($buffer);
            }
        });

        if ($hasErrors) {
            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }

    /** @return mixed[] */
    public function retrieveLocalizationArgumentsFromCurrentArguments(InputInterface $input): array
    {
        $arguments = $input->getArguments();
        $options = $input->getOptions();

        $shellCommands = [];
        foreach ($options as $option => $value) {
            if (is_bool($value) && $value) {
                $shellCommands[] = "--$option";
            } elseif (is_string($value)) {
                $shellCommands[] = "--$option=" . $value;
            }
        }

        // Localizations are rendered as a sub-process. There the progress bar
        // disturbs the output that is returned. We only want normal and error output then.
        $shellCommands[] = '--no-progress';

        foreach ($arguments as $argument) {
            if (is_string($argument)) {
                $shellCommands[] = $argument;
            }
        }

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
                    fn($file) => trim($file) . '.html',
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
                $lastFormat = (count($outputFormats) > 2 ? ',' : '') . ' and ' . strtoupper((string) array_pop($outputFormats));
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
