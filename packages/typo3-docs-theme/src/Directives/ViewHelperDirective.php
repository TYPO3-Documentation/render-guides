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

use phpDocumentor\Guides\Markdown\MarkupLanguageParser;
use phpDocumentor\Guides\Nodes\CodeNode;
use phpDocumentor\Guides\Nodes\CollectionNode;
use phpDocumentor\Guides\Nodes\Inline\PlainTextInlineNode;
use phpDocumentor\Guides\Nodes\InlineCompoundNode;
use phpDocumentor\Guides\Nodes\Node;
use phpDocumentor\Guides\Nodes\ParagraphNode;
use phpDocumentor\Guides\Nodes\SectionNode;
use phpDocumentor\Guides\ReferenceResolvers\AnchorNormalizer;
use phpDocumentor\Guides\RestructuredText\Directives\BaseDirective;
use phpDocumentor\Guides\RestructuredText\Parser\BlockContext;
use phpDocumentor\Guides\RestructuredText\Parser\Directive;
use phpDocumentor\Guides\RestructuredText\Parser\Productions\Rule;
use phpDocumentor\Guides\RestructuredText\TextRoles\GenericLinkProvider;
use Psr\Log\LoggerInterface;
use T3Docs\Typo3DocsTheme\Nodes\ViewHelperArgumentNode;
use T3Docs\Typo3DocsTheme\Nodes\ViewHelperNode;

use function sprintf;

final class ViewHelperDirective extends BaseDirective
{
    public const NAME = 'typo3:viewhelper';

    /**
     * @param Rule<Node> $startingRule
     */
    public function __construct(
        private readonly LoggerInterface      $logger,
        private readonly Rule $startingRule,
        private readonly AnchorNormalizer $anchorNormalizer,
        private readonly MarkupLanguageParser $markupLanguageParser,
        GenericLinkProvider $genericLinkProvider,
    ) {
        $genericLinkProvider->addGenericLink(self::NAME, ViewHelperNode::LINK_TYPE, ViewHelperNode::LINK_PREFIX);
        $genericLinkProvider->addGenericLink(self::NAME . '-argument', ViewHelperArgumentNode::LINK_TYPE, ViewHelperArgumentNode::LINK_PREFIX);
    }

    public function getName(): string
    {
        return self::NAME;
    }

    /** {@inheritDoc} */
    public function processNode(
        BlockContext $blockContext,
        Directive    $directive,
    ): Node {
        $parser = $blockContext->getDocumentParserContext()->getParser();
        $parserContext = $parser->getParserContext();
        if (!$directive->hasOption('source')) {
            $this->logger->warning('The .. typo3:viewhelper:: directive misses the option :source:', $blockContext->getLoggerInformation());
            return $this->getErrorNode();
        }
        $path = $parserContext->absoluteRelativePath($directive->getOptionString('source'));

        $origin = $parserContext->getOrigin();
        if (!$origin->has($path)) {
            $this->logger->warning(sprintf('The .. typo3:viewhelper:: cannot find the source at %s. ', $path), $blockContext->getLoggerInformation());
            return $this->getErrorNode();
        }

        $contents = $origin->read($path);

        if ($contents === false) {
            $this->logger->warning(sprintf('The .. typo3:viewhelper:: cannot load file from path %s. ', $path), $blockContext->getLoggerInformation());
            return $this->getErrorNode();
        }
        $json = json_decode($contents, true);
        if (!is_array($json) || !is_array($json['viewHelpers'] ?? false)) {
            $this->logger->warning(sprintf('The .. typo3:viewhelper:: source at path %s did not contain any ViewHelpers. ', $path), $blockContext->getLoggerInformation());
            return $this->getErrorNode();
        }
        if (!is_array($json['viewHelpers'][$directive->getData()] ?? false)) {
            $this->logger->warning(sprintf('The .. typo3:viewhelper:: source at path %s did not contain ViewHelper "%s". ', $path, $directive->getData()), $blockContext->getLoggerInformation());
            return $this->getErrorNode();
        }

        $sortBy = $directive->getOptionString('sortBy', 'name');

        $noindex = $directive->getOptionBool('noindex');

        $data = $json['viewHelpers'][$directive->getData()];
        $viewHelperNode = $this->getViewHelperNode($directive, $data, $json['sourceEdit'] ?? [], $blockContext, $noindex);
        $arguments = [];
        foreach ($json['viewHelpers'][$directive->getData()]['argumentDefinitions'] ?? [] as $argumentDefinition) {
            if (is_array($argumentDefinition)) {
                $arguments[$this->getString($argumentDefinition, 'name')] = $this->getArgument($argumentDefinition, $viewHelperNode, $noindex);
            }
        }
        if ($sortBy === 'name') {
            ksort($arguments);
        }
        $viewHelperNode->setArguments($arguments);
        $viewHelperNode->setValue($arguments);

        return $viewHelperNode;
    }


    /**
     * @param array<string, mixed> $array
     */
    private function getString(array $array, string $key, string $default = ''): string
    {
        if (!isset($array[$key]) || !is_scalar($array[$key])) {
            return $default;
        }

        return (string) $array[$key];
    }

    /**
     * @param array<string, string> $argumentDefinition
     */
    public function getArgument(array $argumentDefinition, ViewHelperNode $viewHelperNode, bool $noindex): ViewHelperArgumentNode
    {
        $argumentName = $this->getString($argumentDefinition, 'name');
        $argumentId = $this->anchorNormalizer->reduceAnchor($viewHelperNode->getId() . '-' . $argumentName);
        $default = $argumentDefinition['defaultValue'] ?? null;
        if ($default !== null) {
            $default = var_export($default, true);
        }
        return new ViewHelperArgumentNode(
            $viewHelperNode,
            $argumentId,
            $argumentName,
            $this->getString($argumentDefinition, 'type'),
            $this->getString($argumentDefinition, 'description'),
            ($argumentDefinition['required'] ?? false) === true,
            $default,
            $noindex,
        );
    }

    /**
     * @param array<string, mixed> $data
     * @param array<string, array{'sourcePrefix': string, 'editPrefix': string}> $sourceEdit
     */
    private function getViewHelperNode(Directive $directive, array $data, array $sourceEdit, BlockContext $blockContext, bool $noindex): ViewHelperNode
    {
        $rawDocumentation = $this->getString($data, 'documentation');
        $description = [];
        $sections = [];
        $examples = [];
        if (str_contains($rawDocumentation, '```')) {
            $node = $this->markupLanguageParser->parse($blockContext->getDocumentParserContext()->getContext(), $rawDocumentation);
            $collectionNode = new CollectionNode($node->getValue());
            foreach ($node->getValue() as $node) {
                if ($node instanceof ParagraphNode) {
                    $description[] = $node;
                }
                if ($node instanceof CodeNode) {
                    $examples[] = $node;
                }
            }
        } else {
            $rstContentBlockContext = new BlockContext($blockContext->getDocumentParserContext(), $rawDocumentation, false);
            $collectionNode = $this->startingRule->apply($rstContentBlockContext);
            foreach ($collectionNode->getValue() as $node) {
                if (!$node instanceof SectionNode) {
                    $description[] = $node;
                }
            }
            foreach ($collectionNode->getValue() as $node) {
                if ($node instanceof SectionNode) {
                    $title = $node->getTitle()->toString();
                    if (stripos($title, 'example') !== false) { // Case-insensitive check for 'example'
                        $examples[] = $node;
                    } else {
                        $sections[] = $node;
                    }
                }
            }
        }
        $shortClassName = $this->getString($data, 'name');
        $className = $this->getString($data, 'className');
        $nameSpace = $this->getString($data, 'namespace');
        $xmlNamespace = $this->getString($data, 'xmlNamespace');
        $gitHubLink = $sourceEdit[$xmlNamespace]['sourcePrefix'] ?? '';
        if ($gitHubLink !== '') {
            $gitHubLink .= sprintf('%s.php', str_replace('\\', '/', $shortClassName));
        }
        $display = ['tags', 'documentation', 'gitHubLink', 'arguments'];
        if ($directive->hasOption('display')) {
            $display =  array_map('trim', explode(',', $directive->getOptionString('display')));
        }
        $viewHelperId = $this->anchorNormalizer->reduceAnchor($className);
        $viewHelperNode = new ViewHelperNode(
            id: $viewHelperId,
            tagName: $this->getString($data, 'tagName'),
            shortClassName: $shortClassName,
            namespace: $nameSpace,
            className: $className,
            documentation: $collectionNode?->getValue() ?? [],
            description: $description,
            sections: $sections,
            examples: $examples,
            xmlNamespace: $xmlNamespace,
            allowsArbitraryArguments: ($data['allowsArbitraryArguments'] ?? false) === true,
            docTags: $data['docTags'] ?? [],
            gitHubLink: $gitHubLink,
            noindex: $noindex,
            display: $display,
            arguments: [],
        );
        return $viewHelperNode;
    }

    private function getErrorNode(): ParagraphNode
    {
        return new ParagraphNode([new InlineCompoundNode([new PlainTextInlineNode('The ViewHelper cannot be displayed.')])]);
    }
}
