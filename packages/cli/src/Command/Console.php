<?php

declare(strict_types=1);

namespace T3Docs\Cli\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

final class Console extends Command
{
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
        'github_commit_hash' => 'github-commit-hash'
    ];

    /**
     * Maps a Settings.cfg key for [general] to the XML <project> element
     */
    private const MAPPING_PROJECT = [
        'project' => 'project',
        'release' => 'release',
        'version' => 'version',
        'copyright' => 'copyright',
    ];

    /**
     * Maps all Settings.cfg sections that are not covered by this converter
     */
    private const MAPPING_DEPRECATED_SECTIONS = [
        'notify',
        'latex_elements'
    ];

    /**
     * Maps all Settings.cfg sections that are converted
     */
    private const MAPPING_ACCEPTED_SECTIONS = [
        'html_theme_options',
        'general',
        'intersphinx_mapping'
    ];

    public function __construct(
    ) {
        parent::__construct('console');

        $this->addArgument(
            'input',
            InputArgument::REQUIRED,
            'Path to directory where settings.cfg is stored, and guides.xml will be generated.',
        );

        $this->addOption(
            'force',
            null,
            InputOption::VALUE_NONE,
            'When set, forces to overwrite possibly existing XML.'
        );

    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $settingsFile = $input->getArgument('input') . '/Settings.cfg';
        $guidesFile = $input->getArgument('input') . '/guides.xml';

        if (!file_exists($settingsFile)) {
            $output->writeln('<error>Could not open ' . $settingsFile . '</error>');
            return Command::FAILURE;
        }

        if (file_exists($guidesFile) && !$input->getOption('force')) {
            $output->writeln('<error>Target file already exists in specified directory (' . $guidesFile . ')</error>');
            return Command::FAILURE;
        }

        $output->writeln('Converting ' . $settingsFile . ' to ' . $guidesFile . ' ...');

        if (!$this->convertSettingsToGuide($output, $settingsFile, $guidesFile)) {
            $output->writeln('<error>Settings could not be converted. Please check for proper syntax.</error>');
            return Command::FAILURE;
        }

        $output->writeln('Settings converted. You can now delete Settings.cfg and add guides.xml to your repository.');

        return Command::SUCCESS;
    }

    protected function convertSettingsToGuide(OutputInterface $output, string $inputFile, string $outputFile): bool
    {
        // Settings.cfg can be parsed as an INI file. If it fails, bail out.
        try {
            $settings = @parse_ini_file($inputFile, true, INI_SCANNER_RAW);
        } catch (\Exception $e) {
            return false;
        }

        if (!is_array($settings)) {
            return false;
        }

        // This array will hold all setting values that we could not migrate to guides.xml
        $unmigratedSettings = $settings;

        // Holds number of converted settings
        $convertedSettings = 0;

        // Setup basic DOM Document
        $dom = new \DOMDocument("1.0", "UTF-8");
        $dom->preserveWhiteSpace = false;
        $dom->formatOutput = true;

        // Add static <guides> element with proper XMLNS
        $guides = $dom->createElement('guides');
        $guides->setAttribute('xmlns', 'https://www.phpdoc.org/guides');
        $guides->setAttribute('xmlns:xsi', 'http://www.w3.org/2001/XMLSchema-instance');
        $guides->setAttribute('xsi:schemaLocation', 'https://www.phpdoc.org/guides vendor/phpdocumentor/guides-cli/resources/schema/guides.xsd');
        $guides->setAttribute('links-are-relative', 'true');

        // Add <extension> Element. This gets filled with the old "html_theme_options" section.
        $extension = $dom->createElement('extension');
        $extension->setAttribute('class', '\T3Docs\Typo3DocsTheme\DependencyInjection\Typo3DocsThemeExtension');
        if (isset($settings['html_theme_options']) && is_array($settings['html_theme_options'])) {
            foreach(self::MAPPING_SETTING AS $settingsKey => $guidesKey) {
                if (isset($settings['html_theme_options'][$settingsKey])) {
                    $convertedSettings++;
                    $extension->setAttribute($guidesKey, $settings['html_theme_options'][$settingsKey]);
                    unset($unmigratedSettings['html_theme_options'][$settingsKey]);
                }
            }
        }
        $guides->appendChild($extension);

        // Add <project> Element. This gets filled with the old "general" section.
        $project = $dom->createElement('project');
        if (isset($settings['general']) && is_array($settings['general'])) {
            foreach(self::MAPPING_PROJECT AS $settingsKey => $guidesKey) {
                if (isset($settings['general'][$settingsKey])) {
                    $convertedSettings++;
                    $project->setAttribute($guidesKey, $settings['general'][$settingsKey]);
                    unset($unmigratedSettings['general'][$settingsKey]);
                }
            }
        }
        $guides->appendChild($project);

        // Add <inventory> Element. This gets filled with the old "intersphinx_mapping" section.
        if (isset($settings['intersphinx_mapping']) && is_array($settings['intersphinx_mapping'])) {
            foreach($settings['intersphinx_mapping'] as $inventoryKey => $inventoryUrl) {
                $convertedSettings++;
                $inventory = $dom->createElement('inventory');
                $inventory->setAttribute('id', $inventoryKey);
                $inventory->setAttribute('url', $inventoryUrl);
                $guides->appendChild($inventory);
                unset($unmigratedSettings['intersphinx_mapping'][$inventoryKey]);
            }
        }

        // Iterate remaining settings, remove all empty sections.
        // What remains are then missing settings.
        foreach($unmigratedSettings AS $section => $sectionKeys) {
            if (count($sectionKeys) == 0) {
                unset($unmigratedSettings[$section]);
            }
        }

        foreach(self::MAPPING_DEPRECATED_SECTIONS AS $deprecatedSection) {
            if (isset($unmigratedSettings[$deprecatedSection])) {
                unset($unmigratedSettings[$deprecatedSection]);
            }
        }

        // Ignored settings that have no new matching, but we are aware of it
        if (count($unmigratedSettings) > 0) {
            $output->writeln('Note: Some of your settings could not be converted:');
            foreach($unmigratedSettings as $unmigratedSettingSection => $unmigratedSettingValues) {
                $output->writeln('  * ' . $unmigratedSettingSection);

                // For known sections we output the remaining keys
                if (in_array($unmigratedSettingSection, self::MAPPING_ACCEPTED_SECTIONS, true)) {
                    foreach($unmigratedSettingValues AS $unmigratedSettingKey => $unmigratedSettingValue) {
                        $output->writeln('    * ' . $unmigratedSettingKey);
                    }
                }
            }
            $output->writeln('');
        }

        // Attach the <guides> element to the root XML
        $dom->appendChild($guides);

        $fp = fopen($outputFile, 'w');
        if (!$fp) {
            $output->writeln('<error>Could not create file ' . $outputFile . '</error>');
            return false;
        }

        fwrite($fp, (string)$dom->saveXML());

        $output->writeln('<info>' . $convertedSettings . ' Settings were migrated.</info>');

        return true;
    }
}
