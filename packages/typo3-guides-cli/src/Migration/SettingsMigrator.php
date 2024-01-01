<?php

declare(strict_types=1);

namespace T3Docs\GuidesCli\Migration;

use T3Docs\GuidesCli\Migration\Dto\MigrationResult;

class SettingsMigrator
{
    private \DOMDocument $xmlDocument;
    /**
     * @var array<string, array<string, string>>
     */
    private array $legacySettings;
    /**
     * @var array<string, array<string, string>>
     */
    private array $unmigratedSettings = [];
    private int $convertedSettings = 0;

    /**
     * Return the XML document, the number of converted settings and
     * a list of messages for output
     *
     * @param array<string, array<string, string>> $legacySettings
     */
    public function migrate(array $legacySettings): MigrationResult
    {
        $this->legacySettings = $legacySettings;
        $this->unmigratedSettings = $legacySettings;

        $this->xmlDocument = new \DOMDocument('1.0', 'UTF-8');
        $this->xmlDocument->preserveWhiteSpace = true;
        $this->xmlDocument->formatOutput = true;

        $guides = $this->createRootElement();
        $this->createExtensionSection($guides);
        $this->createProjectSection($guides);
        $this->createInventorySection($guides);

        $messages = $this->collectUnmigratedLegacySettings();

        // Attach the <guides> element to the root XML
        $this->xmlDocument->appendChild($guides);

        return new MigrationResult($this->xmlDocument, $this->convertedSettings, $messages);
    }

    private function createRootElement(): \DOMElement
    {
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
    private function createExtensionSection(\DOMElement $parentNode): void
    {
        $extension = $this->xmlDocument->createElement('extension');
        $extension->setAttribute('class', '\T3Docs\Typo3DocsTheme\DependencyInjection\Typo3DocsThemeExtension');

        if (!is_array($this->legacySettings['html_theme_options'] ?? false)) {
            return;
        }

        foreach (HtmlThemeOptions::cases() as $option) {
            if ($this->legacySettings['html_theme_options'][$option->name] ?? false) {
                $this->convertedSettings++;
                $extension->setAttribute($option->value, $this->legacySettings['html_theme_options'][$option->name]);
                unset($this->unmigratedSettings['html_theme_options'][$option->name]);
            }
        }

        $parentNode->append($extension);
    }

    /**
     * Add <project> Element. This gets filled with the old "general" section.
     **/
    private function createProjectSection(\DOMElement $parentNode): void
    {
        $project = $this->xmlDocument->createElement('project');
        if (!is_array($this->legacySettings['general'] ?? false)) {
            return;
        }

        foreach (Project::cases() as $option) {
            if ($this->legacySettings['general'][$option->name] ?? false) {
                $this->convertedSettings++;
                $project->setAttribute(
                    $option->value,
                    $this->legacySettings['general'][$option->name]
                );
                unset($this->unmigratedSettings['general'][$option->name]);
            }
        }

        $parentNode->append($project);
    }

    /**
     * Add <inventory> Element. This gets filled with the old "intersphinx_mapping" section.
     **/
    private function createInventorySection(\DOMElement $parentNode): void
    {
        if (!is_array($this->legacySettings['intersphinx_mapping'] ?? false)) {
            return;
        }

        foreach ($this->legacySettings['intersphinx_mapping'] as $id => $url) {
            unset($this->unmigratedSettings['intersphinx_mapping'][$id]);
            $this->convertedSettings++;

            $inventory = $this->xmlDocument->createElement('inventory');
            $inventory->setAttribute('id', $id);
            $inventory->setAttribute('url', $url);
            $parentNode->appendChild($inventory);
        }
    }

    private function collectUnmigratedLegacySettings(): array
    {
        $messages = [];

        // Iterate remaining settings, remove all empty sections.
        // What remains are then missing settings.
        foreach ($this->unmigratedSettings as $section => $sectionKeys) {
            if ($sectionKeys === []) {
                unset($this->unmigratedSettings[$section]);
            }
        }

        foreach (Deprecated::cases() as $option) {
            if (isset($this->unmigratedSettings[$option->name])) {
                unset($this->unmigratedSettings[$option->name]);
            }
        }

        // Ignored settings that have no new matching, but we are aware of it
        if ($this->unmigratedSettings !== []) {
            $messages[] = 'Note: Some of your settings could not be converted:';
            foreach ($this->unmigratedSettings as $unmigratedSettingSection => $unmigratedSettingValues) {
                $messages[] = '  * ' . $unmigratedSettingSection;

                // For known sections we output the remaining keys
                if (in_array($unmigratedSettingSection, Sections::names(), true)) {
                    foreach ($unmigratedSettingValues as $unmigratedSettingKey => $unmigratedSettingValue) {
                        $messages[] = '    * ' . $unmigratedSettingKey;
                    }
                }
            }
        }

        return $messages;
    }
}
