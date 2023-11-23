<?php

declare(strict_types=1);

namespace T3Docs\GuidesCli\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

final class MigrateSettingsCommand extends Command
{
    protected static $defaultName = 'migrate';

    /**
     * Maps a Settings.cfg key for [html_theme_options] to the XML <extension> element
     */
    private const MAPPING_SETTING = [
        'project_home' => 'project-home',
        'project_contact' => 'project-contact',
        'project_repository' => 'project-repository',
        'project_issues' => 'project-issues',
        'project_discussions' => 'project-discussions',

        'use_opensearch' => 'use-opensearch',

        'github_revision_msg' => 'github-revision-msg',
        'github_branch' => 'edit-on-github-branch',
        'github_repository' => 'edit-on-github',
        'github_sphinx_locale' => 'github-sphinx-locale',
        'github_commit_hash' => 'github-commit-hash',
    ];

    /**
     * Maps a Settings.cfg key for [general] to the XML <project> element
     */
    private const MAPPING_PROJECT = [
        'project' => 'title',
        'release' => 'release',
        'version' => 'version',
        'copyright' => 'copyright',
    ];

    /**
     * Maps all Settings.cfg sections that are not covered by this converter
     */
    private const MAPPING_DEPRECATED_SECTIONS = [
        'notify',
        'latex_elements',
    ];

    /**
     * Maps all Settings.cfg sections that are converted
     */
    private const MAPPING_ACCEPTED_SECTIONS = [
        'html_theme_options',
        'general',
        'intersphinx_mapping',
    ];

    /** @var \DOMDocument Holds the XML document that will be written (guides.xml) */
    private \DOMDocument $xmlDocument;

    /** @var array Holds an array of parsed settings from Settings.cfg */
    private array $settings = [];

    /** @var array Will hold an array of Settings.cfg keys that were not converted */
    private array $unmigratedSettings = [];

    /** @var int The number of successfully converted settings */
    private int $convertedSettings = 0;

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

        if (!file_exists($settingsFile) || !is_readable($settingsFile)) {
            $output->writeln('<error>Could not locate or open ' . $settingsFile . '</error>');
            return Command::FAILURE;
        }

        if (file_exists($guidesFile) && !$input->getOption('force')) {
            $output->writeln('<error>Target file already exists in specified directory (' . $guidesFile . ')</error>');
            return Command::FAILURE;
        }

        $output->writeln('Migrating ' . $settingsFile . ' to ' . $guidesFile . ' ...');

        if (!$this->convertSettingsToGuide($output, $settingsFile, $guidesFile)) {
            $output->writeln('<error>Settings could not be converted. Please check for proper syntax.</error>');
            return Command::FAILURE;
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
            foreach (self::MAPPING_SETTING as $settingsKey => $guidesKey) {
                if (isset($this->settings['html_theme_options'][$settingsKey])) {
                    $this->convertedSettings++;
                    $extension->setAttribute($guidesKey, $this->settings['html_theme_options'][$settingsKey]);
                    unset($this->unmigratedSettings['html_theme_options'][$settingsKey]);
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
            foreach (self::MAPPING_PROJECT as $settingsKey => $guidesKey) {
                if (isset($this->settings['general'][$settingsKey])) {
                    $this->convertedSettings++;
                    $project->setAttribute($guidesKey, $this->settings['general'][$settingsKey]);
                    unset($this->unmigratedSettings['general'][$settingsKey]);
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

        foreach (self::MAPPING_DEPRECATED_SECTIONS as $deprecatedSection) {
            if (isset($this->unmigratedSettings[$deprecatedSection])) {
                unset($this->unmigratedSettings[$deprecatedSection]);
            }
        }

        // Ignored settings that have no new matching, but we are aware of it
        if (count($this->unmigratedSettings) > 0) {
            $output->writeln('Note: Some of your settings could not be converted:');
            foreach ($this->unmigratedSettings as $unmigratedSettingSection => $unmigratedSettingValues) {
                $output->writeln('  * ' . $unmigratedSettingSection);

                // For known sections we output the remaining keys
                if (in_array($unmigratedSettingSection, self::MAPPING_ACCEPTED_SECTIONS, true)) {
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
        // Settings.cfg can be parsed as an INI file. If it fails, bail out.
        $settingsContent = file_get_contents($inputFile);

        if (!is_string($settingsContent)) {
            return false;
        }
        // Remove lines starting with a hashtag and optional whitespace
        $filteredContent = preg_replace('/^\s*#.*$/m', '', $settingsContent);
        $settings = parse_ini_string($filteredContent, true, INI_SCANNER_RAW);

        if (!is_array($settings)) {
            return false;
        }

        $this->settings = $settings;

        // This array will hold all setting values that we could not migrate to guides.xml
        $this->unmigratedSettings = $settings;

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
