<?php

declare(strict_types=1);

namespace T3Docs\GuidesCli\Migration;

use T3Docs\GuidesCli\Migration\Dto\MigrationResult;
use T3Docs\VersionHandling\DefaultInventories;
use T3Docs\VersionHandling\Typo3VersionMapping;

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
    private string $detectedVersion = 'stable';

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
        $extension = $this->createExtensionSection();
        $project = $this->createProjectSection();
        $inventories = $this->createInventorySection();
        $extension->setAttribute('typo3-core-preferred', $this->detectedVersion);

        $guides->append($extension);
        if ($project !== null) {
            $guides->append($project);
        }
        foreach ($inventories as $inventory) {
            $guides->append($inventory);
        }

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
     * Create <extension> Element. This gets filled with the old "html_theme_options" section.
     **/
    private function createExtensionSection(): \DOMElement
    {
        $extension = $this->xmlDocument->createElement('extension');
        $extension->setAttribute('class', '\T3Docs\Typo3DocsTheme\DependencyInjection\Typo3DocsThemeExtension');

        if (!is_array($this->legacySettings['html_theme_options'] ?? false)) {
            return $extension;
        }

        foreach (HtmlThemeOptions::cases() as $option) {
            if ($this->legacySettings['html_theme_options'][$option->name] ?? false) {
                $this->convertedSettings++;
                $extension->setAttribute($option->value, $this->legacySettings['html_theme_options'][$option->name]);
                unset($this->unmigratedSettings['html_theme_options'][$option->name]);
            }
        }

        return $extension;
    }

    /**
     * Create <project> Element. This gets filled with the old "general" section.
     **/
    private function createProjectSection(): \DOMElement|null
    {
        $project = $this->xmlDocument->createElement('project');
        if (!is_array($this->legacySettings['general'] ?? false)) {
            return null;
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
        return $project;
    }

    /**
     * Add <inventory> Element. This gets filled with the old "intersphinx_mapping" section.
     * @return list<\DOMElement>
     **/
    private function createInventorySection(): array
    {
        $inventories = [];
        if (!is_array($this->legacySettings['intersphinx_mapping'] ?? false)) {
            return [];
        }
        $version = null;

        foreach ($this->legacySettings['intersphinx_mapping'] as $id => $url) {
            unset($this->unmigratedSettings['intersphinx_mapping'][$id]);
            $this->convertedSettings++;

            if ($defaultInventory = DefaultInventories::tryFrom($id)) {
                $defaultUrl = $defaultInventory->getUrl();
                if ($url === $defaultUrl) {
                    continue;
                } else {
                    $allowedVersions = Typo3VersionMapping::getAllVersions();
                    $urlDifference = $this->getStringDifference($url, $defaultUrl);
                    if (in_array($urlDifference, $allowedVersions, true)) {
                        $version ??= $urlDifference;
                        if ($version === $urlDifference) {
                            continue;
                        }
                    }
                }
            }
            $inventory = $this->xmlDocument->createElement('inventory');
            $inventory->setAttribute('id', $id);
            $inventory->setAttribute('url', $url);
            $inventories[] = $inventory;
        }
        if (is_string($version) && Typo3VersionMapping::Stable->getVersion() !== $version) {
            $this->detectedVersion = $version;
        }
        return $inventories;
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

    public function getStringDifference(string $url, string $defaultUrl): string
    {
        $commonPrefixLength = strspn($url, $defaultUrl);

        $differingPart1 = substr($url, $commonPrefixLength);
        $differingPart2 = substr($defaultUrl, $commonPrefixLength);

        $commonSuffixLength = strspn(strrev($differingPart1), strrev($differingPart2));
        $differingPart1 = substr($differingPart1, 0, -$commonSuffixLength);
        return $differingPart1;
    }
}
