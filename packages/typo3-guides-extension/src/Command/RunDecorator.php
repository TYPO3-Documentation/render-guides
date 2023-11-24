<?php

namespace T3Docs\GuidesExtension\Command;

use phpDocumentor\Guides\Cli\Command\Run;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

final class RunDecorator extends Command
{
    private const DEFAULT_OUTPUT_DIRECTORY = 'Documentation-GENERATED-temp';

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
    public function __construct(Run $innerCommand)
    {
        parent::__construct($innerCommand->getName());
        $this->innerCommand = $innerCommand;
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
            $guessedInput = $this->guessInput($output);
        }

        if (!isset($options['--output'])) {
            $options['--output'] = getcwd() . '/' . self::DEFAULT_OUTPUT_DIRECTORY;
        }

        $input = new ArrayInput(
            [
                ...$arguments,
                ...$options,
                ...$guessedInput ?? [],
            ],
            $this->getDefinition()
        );

        return $this->innerCommand->execute($input, $output);
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
    private function guessInput(OutputInterface $output): array
    {
        $currentDirectory = getcwd();
        if ($currentDirectory === false) {
            return [];
        }

        $inputDirectory = $currentDirectory . '/Documentation';

        if (is_dir($inputDirectory)) {
            if ($output->isVerbose()) {
                $output->writeln(sprintf('Input directory not specified, using %s', $inputDirectory));
            }

            foreach (self::INDEX_FILE_NAMES as $filename => $extension) {
                if (file_exists($inputDirectory . DIRECTORY_SEPARATOR . $filename)) {
                    return [
                        'input' => $inputDirectory,
                        '--input-format' => $extension,
                    ];
                }
            }
        }

        if ($output->isVerbose()) {
            $output->writeln('Index documentation file not found, trying README.rst or README.md');
        }

        foreach (self::FALLBACK_FILE_NAMES as $filename => $extension) {
            if (file_exists($currentDirectory . DIRECTORY_SEPARATOR . $filename)) {
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
