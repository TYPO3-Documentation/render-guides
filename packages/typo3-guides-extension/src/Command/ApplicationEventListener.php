<?php

declare(strict_types=1);
namespace T3Docs\GuidesExtension\Command;

use Symfony\Component\Console\Event\ConsoleCommandEvent;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\OutputInterface;

final class ApplicationEventListener
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
    public function __invoke(ConsoleCommandEvent $event): void
    {
        $input = $event->getInput();
        $output = $event->getOutput();
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
            ]
        );

        if ($output->isDebug()) {
            $readableOutput = "<info>Options:</info>\n";
            $readableOutput .= print_r($input->getOptions(), true);

            $readableOutput .= "<info>Arguments:</info>\n";
            $readableOutput .= print_r($input->getArguments(), true);

            $readableOutput .= "<info>Guessed Input:</info>\n";
            $readableOutput .= print_r($guessedInput, true);

            $output->writeln(sprintf("<info>DEBUG</info> Using parameters:\n%s", $readableOutput));
        }

        // Todo: How do we set the new input

        $event->getOutput()->writeln(
            sprintf(
                'We are in the ApplicationEventListener',
            ),
        );
    }

    /** @return array<string, string> */
    private function guessInput(OutputInterface $output): array
    {
        $currentDirectory = getcwd();
        if ($currentDirectory === false) {
            if ($output->isDebug()) {
                $output->writeln('<info>DEBUG</info> Could not fetch current working directory.');
            }

            return [];
        }

        $inputDirectory = $currentDirectory . '/Documentation';

        if (is_dir($inputDirectory)) {
            if ($output->isVerbose()) {
                $output->writeln(sprintf('<info>INFO</info> Input directory not specified, using %s', $inputDirectory));
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
