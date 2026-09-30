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

use phpDocumentor\Guides\Nodes\CollectionNode;
use phpDocumentor\Guides\Nodes\InlineCompoundNode;
use phpDocumentor\Guides\Nodes\Node;
use phpDocumentor\Guides\ReferenceResolvers\AnchorNormalizer;
use phpDocumentor\Guides\RestructuredText\Directives\SubDirective;
use phpDocumentor\Guides\RestructuredText\Nodes\ConfvalNode;
use phpDocumentor\Guides\RestructuredText\Parser\BlockContext;
use phpDocumentor\Guides\RestructuredText\Parser\Directive;
use phpDocumentor\Guides\RestructuredText\Parser\Productions\Rule;
use phpDocumentor\Guides\RestructuredText\TextRoles\GenericLinkProvider;
use Psr\Log\LoggerInterface;
use T3Docs\Typo3DocsTheme\ConfvalFields\ConfvalFields;
use T3Docs\Typo3DocsTheme\Nodes\ConfvalMenuNode;

use function sprintf;

class ConfvalMenuDirective extends SubDirective
{
    public const NAME = 'confval-menu';
    public function __construct(
        Rule $startingRule,
        GenericLinkProvider $genericLinkProvider,
        private readonly AnchorNormalizer $anchorReducer,
        private readonly ConfvalFields $confvalFields,
        private readonly LoggerInterface $logger,
    ) {
        parent::__construct($startingRule);
        $genericLinkProvider->addGenericLink(self::NAME, ConfvalMenuNode::LINK_TYPE, ConfvalMenuNode::LINK_PREFIX);
    }
    protected function processSub(
        BlockContext   $blockContext,
        CollectionNode $collectionNode,
        Directive      $directive,
    ): Node|null {
        $originalChildren = $collectionNode->getChildren();
        $childConfvals = [];
        foreach ($originalChildren as $child) {
            if ($child instanceof ConfvalNode) {
                $child = $child->withOptions(array_merge($child->getOptions(), ['isConfval' => true]));
                $childConfvals[] = $child;
            }
        }
        $fields = [];
        $reservedParameterNames = [
            'name',
            'class',
            'caption',
            'display',
            'exclude-noindex',
            'exclude',
        ];
        foreach ($directive->getOptions() as $option) {
            if (in_array($option->getName(), $reservedParameterNames, true)) {
                continue;
            }
            $value = [];
            if (is_string($option->getValue()) && str_starts_with($option->getValue(), 'max=')) {
                $value['max'] = intval(str_replace('max=', '', $option->getValue()));
            }
            $fields[$option->getName()] = $value;

            // A column is matched to a field by its exact name, and one that
            // names no field stays empty without a word. @see ConfvalFields
            if ($this->confvalFields->isChecked() && !$this->confvalFields->isAllowed($option->getName())) {
                $this->logger->warning(sprintf(
                    'The confval-menu column "%s" is not a field the manual declares, so it stays empty. %s',
                    $option->getName(),
                    $this->confvalFields->advice($option->getName()),
                ), $blockContext->getLoggerInformation());
            }
        }
        $exclude = explode(',', $directive->getOptionString('exclude'));
        $anchorReducer = $this->anchorReducer;
        $exclude = array_map(function ($element) use ($anchorReducer) {
            return $anchorReducer->reduceAnchor($element);
        }, $exclude);
        $id = $directive->getOptionString(
            'name',
            $directive->getOptionString(
                'caption',
                $blockContext->getDocumentParserContext()->getDocument()->getFilePath()
            )
        );
        $id = $this->anchorReducer->reduceAnchor($id);
        return new ConfvalMenuNode(
            $id,
            $directive->getData(),
            $directive->getDataNode() ?? new InlineCompoundNode(),
            $originalChildren,
            $directive->getOptionString('caption'),
            $childConfvals,
            $fields,
            $directive->getOptionString('display', 'list'),
            $directive->getOptionBool('exclude-noindex'),
            $exclude,
            $directive->getOptionBool('noindex'),
        );
    }
    public function getName(): string
    {
        return self::NAME;
    }
}
