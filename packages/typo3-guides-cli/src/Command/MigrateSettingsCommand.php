<?php

declare(strict_types=1);

namespace T3Docs\GuidesCli\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use T3Docs\GuidesCli\Migration\SettingsMigrator;
use T3Docs\GuidesCli\Repository\LegacySettingsRepository;

final class MigrateSettingsCommand extends Command
{
    protected static $defaultName = 'migrate';

    private readonly LegacySettingsRepository $legacySettingsRepository;
    private readonly SettingsMigrator $settingsMigrator;

    /** @var \DOMDocument Holds the XML document that will be written (guides.xml) */
    private \DOMDocument $xmlDocument;

    /** @var array Will hold an array of messages for output */
    private array $migrationMessages = [];

    /** @var int The number of successfully converted settings */
    private int $convertedSettings = 0;

    public function __construct(string $name = null)
    {
        parent::__construct($name);
        $this->legacySettingsRepository = new LegacySettingsRepository();
        $this->settingsMigrator = new SettingsMigrator();
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
            if (!$this->convertSettingsToGuide($output, $settingsFile, $guidesFile)) {
                $output->writeln('<error>Settings could not be converted. Please check for proper syntax.</error>');
                return Command::FAILURE;
            }

            foreach ($this->migrationMessages as $message) {
                $output->writeln($message);
            }
        } catch (\Exception $e) {
            $output->writeln('<error>' . $e->getMessage() . '</error>');
        }

        $output->writeln('Settings converted. You can now delete Settings.cfg and add guides.xml to your repository.');

        return Command::SUCCESS;
    }

    private function convertSettingsToGuide(OutputInterface $output, string $inputFile, string $outputFile): bool
    {
        $legacySettings = $this->legacySettingsRepository->get($inputFile);
        [$this->xmlDocument, $this->convertedSettings, $this->migrationMessages]
            = $this->settingsMigrator->migrate($legacySettings);

        return $this->writeXmlDocument($outputFile, $output);
    }

    private function writeXmlDocument(string $outputFile, OutputInterface $output): bool
    {
        $fp = fopen($outputFile, 'w');
        if (!$fp) {
            $output->writeln('<error>Could not create file ' . $outputFile . '</error>');
            return false;
        }

        fwrite($fp, (string)$this->xmlDocument->saveXML());

        $output->writeln('<info>' . $this->convertedSettings . ' settings were migrated.</info>');

        return true;
    }
}
