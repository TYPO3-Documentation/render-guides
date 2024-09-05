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
            $contents = $this->loadFileFromDocumentation($blockContext, $directive);
            // Parse the YAML content
            $yamlData = Yaml::parse($contents);

            if (!is_array($yamlData) || !is_array($yamlData['settings'] ?? false)) {
                throw new FileLoadingException(sprintf('The .. typo3:site-set-settings:: source at path %s did not contain any settings ', $directive->getData()));
            }
        } catch (FileLoadingException $exception) {
            $this->logger->warning($exception->getMessage(), $blockContext->getLoggerInformation());
            return $this->getErrorNode();
        }
        return $this->buildConfvalMenu($directive, $yamlData['settings']);
    }

    /**
     * @throws \League\Flysystem\FileNotFoundException
     * @throws FileLoadingException
     */
    public function loadFileFromDocumentation(BlockContext $blockContext, Directive $directive): string
    {
        $parser = $blockContext->getDocumentParserContext()->getParser();
        $parserContext = $parser->getParserContext();
        /** @var Filesystem $origin */
        $origin = $parserContext->getOrigin();
        /** @var Local $adapter */
        $adapter = $origin->getAdapter();
        $pathPrefix = (string)$adapter->getPathPrefix();

        // The path delivered via the directive like:
        // ..  typo3:site-set-settings:: PROJECT:/Configuration/Sets/FluidStyledContent/settings.definitions.yaml
        $setConfigurationFile = $directive->getData();

        // By default, the RST files are placed inside a "Documentation" subdirectory.
        // When using the docker container, this origin root path is then set to "/project/Documentation".
        // No files on the "/project/" directory level can usually be accessed, even though they may belong
        // to TYPO3 core/third-party extensions that the Documentation belongs to directory-wise.
        // To allow files to be retrieved on the EXTENSION-level, instead of DOCUMENTATION-level,
        // a special string "PROJECT:" is evaluated here.
        // If a path starts with that notation, it will be referenced from the "/project/..." directory level.
        // It will not break out of the "/project/" mapping!
        if (str_starts_with($setConfigurationFile, 'PROJECT:')) {
            // This will replace "PROJECT:/Configuration/Sets/File.yaml" with "/Configuration/Sets/File.yaml"
            // and is then passed to absoluteRelativePath() which will set $path = "/Configuration/Sets/File.yaml",
            // but ensure no "../../../" or other path traversal is allowed.
            $path = $parserContext->absoluteRelativePath(str_replace('PROJECT:', '', $setConfigurationFile));

            // Get the current origin Path, usually "/project/Documentation/", and go one level up.
            $newOriginPath = dirname($pathPrefix) . '/';

            // Temporarily change the path prefix now to "/project/"
            $adapter->setPathPrefix($newOriginPath);
        } else {
            $path = $parserContext->absoluteRelativePath($setConfigurationFile);
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
     */
    public function buildConfvalMenu(Directive $directive, array $settings): ConfvalMenuNode
    {
        $idPrefix = '';
        if ($directive->getOptionString('name') !== '') {
            $idPrefix = $directive->getOptionString('name') . '-';
        }

        $confvals = [];
        foreach ($settings as $key => $setting) {
            $confvals[] = $this->buildConfval($setting, $idPrefix, $key, $directive);
        }
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
     */
    public function buildConfval(array $setting, string $idPrefix, string $key, Directive $directive): ConfvalNode
    {
        $content = [];
        if (is_string($setting['description'] ?? false)) {
            $content[] = new ParagraphNode([
                new InlineCompoundNode([new PlainTextInlineNode((string)$setting['description'])]),
            ]);
        }
        $default = null;
        if (($setting['default'] ?? '') !== '') {
            $default = new InlineCompoundNode([new CodeInlineNode($this->customPrint(($setting['default'])), '')]);
        }
        $additionalFields = [];
        if (is_string($setting['label'] ?? false)) {
            $additionalFields['Label'] = new InlineCompoundNode([new PlainTextInlineNode($setting['label'] ?? '')]);
        }
        if (is_array($setting['enum'] ?? false)) {
            $additionalFields['Enum'] = new InlineCompoundNode([new PlainTextInlineNode((string) json_encode($setting['enum'], JSON_PRETTY_PRINT))]);
        }
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

}
