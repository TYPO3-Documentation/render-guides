<?php

declare(strict_types=1);

/**
 * This file is part of phpDocumentor.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @link https://phpdoc.org
 */

namespace T3Docs\Typo3DocsTheme\Directives;

use League\Flysystem\Adapter\Local;
use League\Flysystem\Filesystem;
use phpDocumentor\Guides\RestructuredText\Nodes\ConfvalNode;
use Symfony\Component\Yaml\Yaml;
use phpDocumentor\Guides\Nodes\Inline\PlainTextInlineNode;
use phpDocumentor\Guides\Nodes\InlineCompoundNode;
use phpDocumentor\Guides\Nodes\Node;
use phpDocumentor\Guides\Nodes\ParagraphNode;
use phpDocumentor\Guides\ReferenceResolvers\AnchorNormalizer;
use phpDocumentor\Guides\RestructuredText\Directives\BaseDirective;
use phpDocumentor\Guides\RestructuredText\Parser\BlockContext;
use phpDocumentor\Guides\RestructuredText\Parser\Directive;
use Psr\Log\LoggerInterface;
use T3Docs\Typo3DocsTheme\Exception\FileLoadingException;
use T3Docs\Typo3DocsTheme\Nodes\ConfvalMenuNode;
use T3Docs\Typo3DocsTheme\Nodes\Inline\CodeInlineNode;

use function sprintf;

final class SiteSetSettingsDirective extends BaseDirective
{
    public const NAME = 'typo3:site-set-settings';
    public const FACET = 'Site Setting';
    public const CATEGORY_FACET = 'Site Setting Category';

    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly AnchorNormalizer $anchorNormalizer,
    ) {}

    public function getName(): string
    {
        return self::NAME;
    }

    /** {@inheritDoc} */
    public function processNode(
        BlockContext $blockContext,
        Directive    $directive,
    ): Node {
        try {
            // The path delivered via the directive like:
            // ..  typo3:site-set-settings:: PROJECT:/Configuration/Sets/FluidStyledContent/settings.definitions.yaml
            $setConfigurationFile = $directive->getData();
            $contents = $this->loadFileFromDocumentation($blockContext, $setConfigurationFile);
            // Parse the YAML content
            $yamlData = Yaml::parse($contents);

            if (!is_array($yamlData) || !is_array($yamlData['settings'] ?? false)) {
                throw new FileLoadingException(sprintf('The .. typo3:site-set-settings:: source at path %s did not contain any settings ', $directive->getData()));
            }
        } catch (FileLoadingException $exception) {
            $this->logger->warning($exception->getMessage(), $blockContext->getLoggerInformation());
            return $this->getErrorNode();
        }


        $labelsFile = null;
        try {
            $configYamlFile = dirname($setConfigurationFile) . '/config.yaml';
            $contents = $this->loadFileFromDocumentation($blockContext, $configYamlFile);
            // Parse the YAML content
            $configYamlData = Yaml::parse($contents);

            if (is_array($configYamlData)) {
                $labelsFile = $configYamlData['labels'] ?? null;
            }
        } catch (FileLoadingException $exception) {
            // ignore, config.yaml isn't required
        }

        $labelContents = '';
        // Assume all EXT: references are relative to the rendered PROJECT
        $labelsFile = $labelsFile ?
            preg_replace('/^EXT:[^\/]*\//', 'PROJECT:/', $labelsFile) :
            dirname($setConfigurationFile) . '/labels.xlf';
        try {
            $labelContents = $this->loadFileFromDocumentation($blockContext, $labelsFile);
        } catch (FileLoadingException $exception) {
            // ignore, labels.xlf isn't required
        }

        $labels = [];
        $descriptions = [];
        $categoryLabels = [];
        if ($labelContents) {
            $xml = new \DOMDocument();
            if ($xml->loadXML($labelContents)) {
                foreach ($xml->getElementsByTagName('trans-unit') as $label) {
                    $id = $label->getAttribute('id');
                    $value = ($label->getElementsByTagName('source')[0] ?? null)?->textContent ?? '';
                    if (!$value) {
                        continue;
                    }
                    if (str_starts_with($id, 'settings.description.')) {
                        $descriptions[substr($id, 21)] = $value;
                    } elseif (str_starts_with($id, 'settings.')) {
                        $labels[substr($id, 9)] = $value;
                    } elseif (str_starts_with($id, 'categories.')) {
                        $categoryLabels[substr($id, 11)] = $value;
                    }
                }
            }
        }

        return $this->buildConfvalMenu($directive, $yamlData['settings'], $yamlData['categories'] ?? [], $labels, $descriptions, $categoryLabels);
    }

    /**
     * @throws \League\Flysystem\FileNotFoundException
     * @throws FileLoadingException
     */
    public function loadFileFromDocumentation(BlockContext $blockContext, string $filename): string
    {
        $parser = $blockContext->getDocumentParserContext()->getParser();
        $parserContext = $parser->getParserContext();
        /** @var Filesystem $origin */
        $origin = $parserContext->getOrigin();
        /** @var Local $adapter */
        $adapter = $origin->getAdapter();
        $pathPrefix = (string)$adapter->getPathPrefix();

        // By default, the RST files are placed inside a "Documentation" subdirectory.
        // When using the docker container, this origin root path is then set to "/project/Documentation".
        // No files on the "/project/" directory level can usually be accessed, even though they may belong
        // to TYPO3 core/third-party extensions that the Documentation belongs to directory-wise.
        // To allow files to be retrieved on the EXTENSION-level, instead of DOCUMENTATION-level,
        // a special string "PROJECT:" is evaluated here.
        // If a path starts with that notation, it will be referenced from the "/project/..." directory level.
        // It will not break out of the "/project/" mapping!
        if (str_starts_with($filename, 'PROJECT:')) {
            // This will replace "PROJECT:/Configuration/Sets/File.yaml" with "/Configuration/Sets/File.yaml"
            // and is then passed to absoluteRelativePath() which will set $path = "/Configuration/Sets/File.yaml",
            // but ensure no "../../../" or other path traversal is allowed.
            $path = $parserContext->absoluteRelativePath(str_replace('PROJECT:', '', $filename));

            // Get the current origin Path, usually "/project/Documentation/", and go one level up.
            $newOriginPath = dirname($pathPrefix) . '/';

            // Temporarily change the path prefix now to "/project/"
            $adapter->setPathPrefix($newOriginPath);
        } else {
            $path = $parserContext->absoluteRelativePath($filename);
        }

        if (!$origin->has($path)) {
            // Revert temporary change to origin (because it being a singleton)
            $adapter->setPathPrefix($pathPrefix);
            throw new FileLoadingException(
                sprintf('The directive .. typo3:site-set-settings:: cannot find the source at %s. ', $path)
            );
        }

        $contents = $origin->read($path);

        // Revert temporary change to origin (because it being a singleton).
        $adapter->setPathPrefix($pathPrefix);

        if ($contents === false) {
            throw new FileLoadingException(sprintf('The .. typo3:site-set-settings:: cannot load file from path %s. ', $path));
        }
        return $contents;
    }

    private function getErrorNode(): ParagraphNode
    {
        return new ParagraphNode([new InlineCompoundNode([new PlainTextInlineNode('The site set settings cannot be displayed.')])]);
    }

    /**
     * @param array<string, array<string, string>> $settings
     * @param array<string, array<string, string>> $categories
     * @param array<string, string> $labels
     * @param array<string, string> $descriptions
     * @param array<string, string> $categoryLabels
     */
    public function buildConfvalMenu(Directive $directive, array $settings, array $categories, array $labels, array $descriptions, array $categoryLabels): ConfvalMenuNode
    {
        $idPrefix = '';
        if ($directive->getOptionString('name') !== '') {
            $idPrefix = $directive->getOptionString('name') . '-';
        }
        $categoryArray = $this->buildCategoryArray($categories, $categoryLabels);
        $rootCategories = [];
        foreach ($categoryArray as $key => $category) {
            if (isset($categoryArray[$category['parent']])) {
                assert(is_array($categoryArray[$category['parent']]['children']));
                $categoryArray[$category['parent']]['children'][] = &$categoryArray[$key];
            } else {
                $rootCategories[] = &$categoryArray[$key];
            }
        }
        foreach ($settings as $key => $setting) {
            $confval = $this->buildConfval($setting, $idPrefix, $key, $directive, $labels, $descriptions, $categoryArray);
            $this->assignConfvalsToCategories($setting['category'] ?? '', $categoryArray, $confval, $rootCategories);
        }
        $confvals = $this->buildCategoryConfvals($rootCategories, $idPrefix, $directive);
        $reservedParameterNames = [
            'name',
            'class',
            'caption',
            'display',
            'noindex',
        ];
        $fields = [];
        foreach ($directive->getOptions() as $option) {
            if (in_array($option->getName(), $reservedParameterNames, true)) {
                continue;
            }
            $value = [];
            if (is_string($option->getValue()) && str_starts_with($option->getValue(), 'max=')) {
                $value['max'] = intval(str_replace('max=', '', $option->getValue()));
            }
            $fields[$option->getName()] = $value;
        }
        $confvalMenu = new ConfvalMenuNode(
            $this->anchorNormalizer->reduceAnchor($directive->getOptionString('name')),
            $directive->getData(),
            $directive->getDataNode() ?? new InlineCompoundNode([]),
            $confvals,
            $directive->getOptionString('caption'),
            $confvals,
            $fields,
            $directive->getOptionString('display', 'table'),
            false,
            [],
            $directive->getOptionBool('noindex'),
        );
        return $confvalMenu;
    }


    /**
     * @param array<string, scalar|array<string, scalar>> $setting
     * @param array<string, string> $labels
     * @param array<string, string> $descriptions
     * @param array<string, array<string, string>> $categoryArray
     */
    public function buildConfval(array $setting, string $idPrefix, string $key, Directive $directive, array $labels, array $descriptions, array $categoryArray): ConfvalNode
    {
        $content = [];
        $description = $setting['description'] ?? $descriptions[$key] ?? false;
        if (is_string($description)) {
            $content[] = new ParagraphNode([
                new InlineCompoundNode([new PlainTextInlineNode($description)]),
            ]);
        }
        $default = null;
        if (($setting['default'] ?? '') !== '') {
            $default = new InlineCompoundNode([new CodeInlineNode($this->customPrint(($setting['default'])), '')]);
        }
        $additionalFields = [];
        $label = $setting['label'] ?? $labels[$key] ?? false;
        if (is_string($label)) {
            $additionalFields['Label'] = new InlineCompoundNode([new PlainTextInlineNode($label)]);
        }
        if (is_array($setting['enum'] ?? false)) {
            $additionalFields['Enum'] = new InlineCompoundNode([new PlainTextInlineNode((string) json_encode($setting['enum'], JSON_PRETTY_PRINT))]);
        }
        $categoryKey = '';
        if (($setting['category'] ?? '') !== '' && is_string($setting['category'])) {
            $categoryKey = $setting['category'];
        }
        $category = $this->getCategoryRootline($categoryArray, $categoryKey);
        if ($category !== '') {
            $additionalFields['Category'] = new InlineCompoundNode([new PlainTextInlineNode($category)]);
        }

        $additionalFields['searchFacet'] = new InlineCompoundNode([new PlainTextInlineNode(self::FACET)]);
        assert(is_scalar($setting['type']));

        $confval = new ConfvalNode(
            $this->anchorNormalizer->reduceAnchor($idPrefix . $key),
            $key,
            new InlineCompoundNode([new CodeInlineNode((string)($setting['type'] ?? ''), '')]),
            false,
            $default,
            $additionalFields,
            $content,
            $directive->getOptionBool('noindex'),
        );
        return $confval;
    }

    private function customPrint(mixed $value): string
    {
        if (is_null($value)) {
            return 'null';
        }
        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }
        if (is_string($value)) {
            return sprintf('"%s"', $value);
        }
        if (is_float($value)) {
            return sprintf('%.2f', $value); // Adjust precision if needed
        }

        if (is_int($value)) {
            return (string)$value;
        }
        if (is_array($value) || is_object($value)) {
            return (string)(json_encode($value, JSON_PRETTY_PRINT));
        }

        return 'unkown'; // For other types or unexpected cases
    }


    /**
     * @param array<string, array<string, string>> $categoryArray
     */
    private function getCategoryRootline(array $categoryArray, string $key): string
    {
        $label = $categoryArray[$key]['label'] ?? $key;
        $parent = $categoryArray[$key]['parent'] ?? '';
        if ($parent === '') {
            return $label;
        }
        if ($label === '') {
            return $label;
        }
        return $this->getCategoryRootline($categoryArray, $parent) . ' > ' . $label;
    }

    /**
     * @param array<string, array<string, mixed>> $categories
     * @param array<string, string> $categoryLabels
     * @return array<string, array<string, mixed>>
     */
    public function buildCategoryArray(array $categories, array $categoryLabels): array
    {
        $categoryArray = [];
        foreach ($categories as $key => $category) {
            $categoryArray[$key] = [
                'label' => $category['label'] ?? $categoryLabels[$key] ?? '',
                'parent' => $category['parent'] ?? '',
                'key' => $key,
                'confvals' => [],
                'children' => [],
            ];
        }
        return $categoryArray;
    }

    /**
     * @param array<string, array<string, mixed>> $categoryArray
     * @param array<array<string, mixed>> $rootCategories
     */
    public function assignConfvalsToCategories(string $category, array &$categoryArray, ConfvalNode $confval, array &$rootCategories): void
    {
        if (is_array($categoryArray[$category]['confvals'] ?? false)) {
            $categoryArray[$category]['confvals'][] = $confval;
        } else {
            $categoryArray[$category] = [
                'label' => '',
                'parent' => '',
                'key' => $category,
                'children' => [],
                'confvals' => [$confval],
            ];
            $rootCategories[] = &$categoryArray[$category];
        }
    }

    /**
     * @param array<array<string, mixed>> $categories
     * @return ConfvalNode[]
     */
    private function buildCategoryConfvals(array $categories, string $idPrefix, Directive $directive): array
    {
        if ($categories === []) {
            return [];
        }
        $confvals = [];
        foreach ($categories as $category) {
            $children = [];
            if (is_array($category['children'] ?? false)) {
                $children = $this->buildCategoryConfvals($category['children'], $idPrefix, $directive);
            }
            $key =  $category['key'];
            if ($key === '') {
                $key = '_global';
            }
            assert(is_string($key));
            $additionalFields = [];
            $additionalFields['searchFacet'] = new InlineCompoundNode([new PlainTextInlineNode(self::CATEGORY_FACET)]);

            $label = $category['label'];
            if ($label !== '') {
                assert(is_string($label));
                $additionalFields['Label'] = new InlineCompoundNode([new PlainTextInlineNode($label)]);
            }
            assert(is_array($category['confvals']));
            $confvals[] = new ConfvalNode(
                $this->anchorNormalizer->reduceAnchor($idPrefix . 'category-' . $key),
                $key,
                null,
                false,
                null,
                $additionalFields,
                array_merge($children, $category['confvals']),
                $directive->getOptionBool('noindex'),
            );
        }
        return $confvals;
    }

}
