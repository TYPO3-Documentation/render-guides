<?php

namespace T3Docs\GuidesExtension\Command;

use phpDocumentor\Guides\Cli\Command\Run;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
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

    private Run $innerCommand;
    public function __construct(Run $innerCommand, private readonly Typo3DocsInputSettings $inputSettings)
    {
        parent::__construct($innerCommand->getName());
        $this->innerCommand = $innerCommand;

        $this->innerCommand->addOption(
            'localization',
            null,
            InputArgument::OPTIONAL,
            'Render a specific localization (for example "de_DE", "ru_RU", ...)',
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
        if ($arguments['input'] === null) {
            $guessedInput = $this->guessInput(self::DEFAULT_INPUT_DIRECTORY, $output);
        } else {
            $guessedInput = [];
        }

        if (!isset($options['--output'])) {
            $options['--output'] = getcwd() . '/' . self::DEFAULT_OUTPUT_DIRECTORY;
        }

        $input = new ArrayInput(
            [
                ...$arguments,
                ...$options,
                ...$guessedInput,
            ],
            $this->getDefinition()
        );

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

        $baseExecution = $this->innerCommand->execute($input, $output);

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
        foreach($baseInputDirectives as $baseInputDirectiveKey => $baseInputDirectiveValue) {
            $localInputDirectives[$baseInputDirectiveKey] = $baseInputDirectiveValue . DIRECTORY_SEPARATOR . $availableLocalization;
        }
        $output->writeln(sprintf('<info>Trying to render %s ...</info>', $availableLocalization));

        $guessInput = $this->guessInput($localInputDirectives['input-file'], $output);
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
        $result = $process->run(function ($type, $buffer) use ($output, &$hasErrors): void {
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

    public function getDescription(): string
    {
        return $this->innerCommand->getDescription();
    }

    public function getHelp(): string
    {
        return $this->innerCommand->getHelp();
    }

    public function setApplication(Application $application = null): void
    {
        parent::setApplication($application);
        $this->innerCommand->setApplication($application);
    }

    /** @return mixed[] */
    public function getUsages(): array
    {
        return $this->innerCommand->getUsages();
    }

    public function getNativeDefinition(): InputDefinition
    {
        return $this->innerCommand->getNativeDefinition();
    }

    public function getSynopsis(bool $short = false): string
    {
        return $this->innerCommand->getSynopsis($short);
    }

    public function getDefinition(): InputDefinition
    {
        return $this->innerCommand->getDefinition();
    }

    public function mergeApplicationDefinition(bool $mergeArgs = true): void
    {
        $this->innerCommand->mergeApplicationDefinition($mergeArgs);
    }

    /** @return array<string, string> */
    private function guessInput(string $inputBaseDirectory, OutputInterface $output): array
    {
        $currentDirectory = getcwd();
        if ($currentDirectory === false) {
            if ($output->isDebug()) {
                $output->writeln('<info>DEBUG</info> Could not fetch current working directory.');
            }

            return [];
        }

        $inputDirectory = $currentDirectory . DIRECTORY_SEPARATOR . $inputBaseDirectory;

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
}
