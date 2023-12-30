<?php

declare(strict_types=1);

namespace T3Docs\GuidesCli\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use T3Docs\GuidesCli\Migration\Processor;

final class MigrateSettingsCommand extends Command
{
    protected static $defaultName = 'migrate';

    private readonly Processor $processor;

    /**
     * Arguments for testing only!
     */
    public function __construct(?Processor $processor = null)
    {
        parent::__construct();
        $this->processor = $processor ?? new Processor();
    }

    protected function configure(): void
    {
        $this->setDescription('Migrates Settings.cfg to guides.xml format.');
        $this->setHelp(
            <<<'EOT'
                The <info>%command.name%</info> command migrates a Settings.cfg in side the
                specified input directory, tries to parse it and convert all known settings
                to the XML format used in the guides.xml file.

                <info>$ php %command.name% [input]</info>

                EOT
        );
        $this->setDefinition([
            new InputArgument(
                'input',
                InputArgument::REQUIRED,
                'Path to the "Documentation" directory where Settings.cfg is stored, and guides.xml will be generated.',
            ),

            new InputOption(
                'force',
                null,
                InputOption::VALUE_NONE,
                'When set, overwrites the guides.xml file, if it exists.'
            ),
        ]);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $settingsFile = $input->getArgument('input') . '/Settings.cfg';
        $guidesFile = $input->getArgument('input') . '/guides.xml';

        if (file_exists($guidesFile) && !$input->getOption('force')) {
            $output->writeln('<error>Target file already exists in specified directory (' . $guidesFile . ')</error>');
            return Command::FAILURE;
        }

        $output->writeln('Migrating ' . $settingsFile . ' to ' . $guidesFile . ' ...');

        try {
            $actual = $this->processor->process($settingsFile, $guidesFile);
            foreach ($actual->migrationMessages as $message) {
                $output->writeln($message);
            }
        } catch (\Exception $e) {
            $output->writeln('<error>' . $e->getMessage() . '</error>');
            return Command::FAILURE;
        }

        $output->writeln(
            \sprintf(
                '%d settings converted. You can now delete Settings.cfg and add guides.xml to your repository.',
                $actual->numberOfConvertedSettings
            )
        );

        return Command::SUCCESS;
    }
}
