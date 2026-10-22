<?php

declare(strict_types=1);

namespace T3Docs\Typo3Api\Documentation\Compiler;

use phpDocumentor\Compiler\ApiDocumentation\ApiDocumentationPass;
use phpDocumentor\Descriptor\Collection;
use phpDocumentor\Descriptor\Interfaces\ApiDocumentationSet;
use phpDocumentor\Descriptor\Interfaces\ElementInterface;
use phpDocumentor\Descriptor\Interfaces\MetadataAwareInterface;
use phpDocumentor\Pipeline\Attribute\Stage;
use phpDocumentor\ProjectDescriptorBuilder;
use phpDocumentor\WithCustomSettings;
use T3Docs\Typo3Api\Documentation\Index\DocumentationIndex;
use T3Docs\Typo3Api\Documentation\Reflection\Documentation;

/**
 * Gives each class, interface, trait and enum the places of the TYPO3
 * documentation that speak of it.
 *
 * The setting "typo3.documentation.manuals" names "manuals.json" of the docs
 * homepage. Without it, nothing is read and the pages show no documentation,
 * so a build without the network still works.
 *
 * The setting "typo3.documentation.exclude" names the manuals not to list, by
 * their shortcodes, separated by commas. How to Document, for example, shows
 * classes only to explain the markup that documents them.
 */
#[Stage(
    'phpdoc.pipeline.api_documentation.compile',
    7900,
    'List where the TYPO3 documentation speaks of a class on the page of the class',
)]
final class DocumentationBuilder extends ApiDocumentationPass implements WithCustomSettings
{
    public const SETTING = 'typo3.documentation.manuals';

    public const EXCLUDE_SETTING = 'typo3.documentation.exclude';

    public function __construct(
        private readonly ProjectDescriptorBuilder $projectDescriptorBuilder,
        private readonly DocumentationIndex $documentationIndex,
    ) {}

    /** @return array<string, bool|string> */
    public function getDefaultSettings(): array
    {
        return [self::SETTING => '', self::EXCLUDE_SETTING => ''];
    }

    protected function process(ApiDocumentationSet $subject): ApiDocumentationSet
    {
        $settings = $this->projectDescriptorBuilder->getProjectDescriptor()->getSettings()->getCustom();
        $manuals = (string)($settings[self::SETTING] ?? '');
        if ($manuals === '') {
            return $subject;
        }

        $excluded = array_values(array_filter(array_map(trim(...), explode(',', (string)($settings[self::EXCLUDE_SETTING] ?? '')))));
        $index = $this->documentationIndex->load(
            $manuals,
            (string)($settings['template.typo3_version'] ?? 'main'),
            $excluded,
        );
        if ($index === []) {
            return $subject;
        }

        foreach ($subject->getIndexes()->fetch('elements', new Collection()) as $element) {
            if (!$element instanceof ElementInterface || !$element instanceof MetadataAwareInterface) {
                continue;
            }

            $documentation = $index[(string)$element->getFullyQualifiedStructuralElementName()] ?? null;
            if ($documentation === null) {
                continue;
            }

            $element->setMetadata([
                ...$element->getMetadata(),
                Documentation::KEY => new Documentation($documentation),
            ]);
        }

        return $subject;
    }
}
