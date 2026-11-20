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

use phpDocumentor\Guides\Compiler\CompilerContextInterface;
use phpDocumentor\Guides\Nodes\InlineCompoundNode;
use phpDocumentor\Guides\ReferenceResolvers\AnchorNormalizer;
use phpDocumentor\Guides\RestructuredText\Directives\Attributes;
use phpDocumentor\Guides\RestructuredText\Directives\BaseDirective;
use phpDocumentor\Guides\RestructuredText\Directives\OptionType;
use phpDocumentor\Guides\RestructuredText\Nodes\ConfvalNode;
use phpDocumentor\Guides\RestructuredText\Nodes\DirectiveNode;
use phpDocumentor\Guides\RestructuredText\TextRoles\GenericLinkProvider;
use Psr\Log\LoggerInterface;
use T3Docs\Typo3DocsTheme\ConfvalFields\ConfvalFields;
use T3Docs\Typo3DocsTheme\Nodes\ConfvalMenuNode;

use function sprintf;

#[Attributes\Directive(name: 'confval-menu', parseUndeclaredOptionsAsInlineMarkup: true)]
#[Attributes\Option('name', description: 'Unique identifier of the confval menu')]
#[Attributes\Option('class', description: 'CSS classes for the confval menu')]
#[Attributes\Option('caption', description: 'Caption of the confval menu')]
#[Attributes\Option('display', default: 'list', description: 'Display mode of the confval menu')]
#[Attributes\Option('exclude-noindex', type: OptionType::Boolean, default: false, description: 'Exclude confvals marked as noindex')]
#[Attributes\Option('exclude', description: 'Comma-separated list of confval identifiers to exclude')]
#[Attributes\Option('parent', description: 'Confval whose properties are listed by this menu')]
#[Attributes\Option('noindex', type: OptionType::Boolean, default: false, description: 'Do not index the confval menu')]
final class ConfvalMenuDirective extends BaseDirective
{
    public const NAME = 'confval-menu';

    public function __construct(
        GenericLinkProvider $genericLinkProvider,
        private readonly AnchorNormalizer $anchorReducer,
        private readonly ConfvalFields $confvalFields,
        private readonly LoggerInterface $logger,
    ) {
        $genericLinkProvider->addGenericLink(self::NAME, ConfvalMenuNode::LINK_TYPE, ConfvalMenuNode::LINK_PREFIX);
    }

    public function createNode(
        DirectiveNode $directiveNode,
        CompilerContextInterface $compilerContext,
    ): ConfvalMenuNode {
        $directive = $directiveNode->getDirective();
        $originalChildren = $directiveNode->getChildren();
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
            'parent',
            'noindex',
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
                ), $compilerContext->getLoggerInformation());
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
                $compilerContext->getDocumentNode()->getFilePath()
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
            parent: $directive->hasOption('parent') ? $this->anchorReducer->reduceAnchor($directive->getOptionString('parent')) : '',
        );
    }
}
