<?php

declare(strict_types=1);

namespace T3Docs\GuidesCli\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use T3Docs\GuidesCli\Migration\Deprecated;
use T3Docs\GuidesCli\Migration\HtmlThemeOptions;
use T3Docs\GuidesCli\Migration\Project;
use T3Docs\GuidesCli\Migration\Sections;
use T3Docs\GuidesCli\Repository\LegacySettingsRepository;

final class MigrateSettingsCommand extends Command
{
    protected static $defaultName = 'migrate';

    private readonly LegacySettingsRepository $legacySettingsRepository;

    /** @var \DOMDocument Holds the XML document that will be written (guides.xml) */
    private \DOMDocument $xmlDocument;

    /** @var array Holds an array of parsed settings from Settings.cfg */
    private array $settings = [];

    /** @var array Will hold an array of Settings.cfg keys that were not converted */
    private array $unmigratedSettings = [];

    /** @var int The number of successfully converted settings */
    private int $convertedSettings = 0;

    public function __construct(string $name = null)
    {
        parent::__construct($name);
        $this->legacySettingsRepository = new LegacySettingsRepository();
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
        } catch (\Exception $e) {
            $output->writeln('<error>' . $e->getMessage() . '</error>');
        }

        $output->writeln('Settings converted. You can now delete Settings.cfg and add guides.xml to your repository.');

        return Command::SUCCESS;
    }

    /**
     * Setup basic DOM Document
     **/
    private function createXmlSectionGuides(): \DOMElement
    {
        // Add static <guides> element with proper XMLNS
        $guides = $this->xmlDocument->createElement('guides');
        $guides->setAttribute('xmlns', 'https://www.phpdoc.org/guides');
        $guides->setAttribute('xmlns:xsi', 'http://www.w3.org/2001/XMLSchema-instance');
        $guides->setAttribute('xsi:schemaLocation', 'https://www.phpdoc.org/guides ../vendor/phpdocumentor/guides-cli/resources/schema/guides.xsd');
        $guides->setAttribute('links-are-relative', 'true');

        return $guides;
    }

    /**
     * Add <extension> Element. This gets filled with the old "html_theme_options" section.
     **/
    private function createXmlSectionExtension(\DOMElement $parentNode): bool
    {
        $extension = $this->xmlDocument->createElement('extension');
        $extension->setAttribute('class', '\T3Docs\Typo3DocsTheme\DependencyInjection\Typo3DocsThemeExtension');
        if (is_array($this->settings['html_theme_options'] ?? false)) {
            foreach (HtmlThemeOptions::cases() as $option) {
                if (isset($this->settings['html_theme_options'][$option->name])) {
                    $this->convertedSettings++;
                    $extension->setAttribute($option->value, $this->settings['html_theme_options'][$option->name]);
                    unset($this->unmigratedSettings['html_theme_options'][$option->name]);
                }
            }

            $parentNode->append($extension);
            return true;
        }

        return false;
    }

    /**
     * Add <project> Element. This gets filled with the old "general" section.
     **/
    private function createXmlSectionProject(\DOMElement $parentNode): bool
    {
        $project = $this->xmlDocument->createElement('project');
        if (is_array($this->settings['general'] ?? false)) {
            foreach (Project::cases() as $option) {
                if (isset($this->settings['general'][$option->name])) {
                    $this->convertedSettings++;
                    $project->setAttribute($option->value, $this->settings['general'][$option->name]);
                    unset($this->unmigratedSettings['general'][$option->name]);
                }
            }

            $parentNode->append($project);

            return true;
        }

        return false;
    }

    /**
     * Add <inventory> Element. This gets filled with the old "intersphinx_mapping" section.
     **/
    private function createXmlSectionInventory(\DOMElement $parentNode): bool
    {
        if (is_array($this->settings['intersphinx_mapping'] ?? false)) {
            $hasAnyMapping = false;
            foreach ($this->settings['intersphinx_mapping'] as $inventoryKey => $inventoryUrl) {
                unset($this->unmigratedSettings['intersphinx_mapping'][$inventoryKey]);
                $this->convertedSettings++;
                $inventoryKey = trim($inventoryKey);
                if (!str_starts_with($inventoryKey, '#')) {
                    $inventory = $this->xmlDocument->createElement('inventory');
                    $inventory->setAttribute('id', $inventoryKey);
                    $inventory->setAttribute('url', $inventoryUrl);
                    $parentNode->appendChild($inventory);
                    $hasAnyMapping = true;
                }
            }

            if ($hasAnyMapping) {
                return true;
            }
        }

        return false;
    }

    private function checkUnmigratedSettings(OutputInterface $output): bool
    {
        // Iterate remaining settings, remove all empty sections.
        // What remains are then missing settings.
        foreach ($this->unmigratedSettings as $section => $sectionKeys) {
            if (count($sectionKeys) == 0) {
                unset($this->unmigratedSettings[$section]);
            }
        }

        foreach (Deprecated::cases() as $option) {
            if (isset($this->unmigratedSettings[$option->name])) {
                unset($this->unmigratedSettings[$option->name]);
            }
        }

        // Ignored settings that have no new matching, but we are aware of it
        if (count($this->unmigratedSettings) > 0) {
            $output->writeln('Note: Some of your settings could not be converted:');
            foreach ($this->unmigratedSettings as $unmigratedSettingSection => $unmigratedSettingValues) {
                $output->writeln('  * ' . $unmigratedSettingSection);

                // For known sections we output the remaining keys
                if (in_array($unmigratedSettingSection, Sections::names(), true)) {
                    foreach ($unmigratedSettingValues as $unmigratedSettingKey => $unmigratedSettingValue) {
                        $output->writeln('    * ' . $unmigratedSettingKey);
                    }
                }
            }
            $output->writeln('');

            return true;
        }

        return false;
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

    private function convertSettingsToGuide(OutputInterface $output, string $inputFile, string $outputFile): bool
    {
        $this->settings = $this->legacySettingsRepository->get($inputFile);

        // This array will hold all setting values that we could not migrate to guides.xml
        $this->unmigratedSettings = $this->settings;

        $this->xmlDocument = new \DOMDocument('1.0', 'UTF-8');
        $this->xmlDocument->preserveWhiteSpace = true;
        $this->xmlDocument->formatOutput = true;

        $guides = $this->createXmlSectionGuides();
        $this->createXmlSectionExtension($guides);
        $this->createXmlSectionProject($guides);
        $this->createXmlSectionInventory($guides);

        $this->checkUnmigratedSettings($output);

        // Attach the <guides> element to the root XML
        $this->xmlDocument->appendChild($guides);

        return $this->writeXmlDocument($outputFile, $output);
    }
}
