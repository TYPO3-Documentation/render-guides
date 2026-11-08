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
use phpDocumentor\Guides\Nodes\ParagraphNode;
use phpDocumentor\Guides\Compiler\CompilerContextInterface;
use phpDocumentor\Guides\ReferenceResolvers\AnchorNormalizer;
use phpDocumentor\Guides\RestructuredText\Directives\Attributes;
use phpDocumentor\Guides\RestructuredText\Directives\BaseDirective;
use phpDocumentor\Guides\RestructuredText\Directives\OptionType;
use phpDocumentor\Guides\RestructuredText\Nodes\DirectiveNode;
use phpDocumentor\Guides\RestructuredText\Parser\Directive;
use phpDocumentor\Guides\RestructuredText\TextRoles\GenericLinkProvider;
use Psr\Log\LoggerInterface;
use T3Docs\Typo3DocsTheme\Nodes\Typo3FileNode;

#[Attributes\Directive(name: 'typo3:file')]
#[Attributes\Option('name', description: 'Identifier of the file definition')]
#[Attributes\Option('path', description: 'Path relative to the current file')]
#[Attributes\Option('language', description: 'Language of the file')]
#[Attributes\Option('regex', description: 'Regular expression used to match the file')]
#[Attributes\Option('scope', description: 'Scope of the file definition')]
#[Attributes\Option('composerPath', description: 'Composer path of the file')]
#[Attributes\Option('classicPath', description: 'Classic installation path of the file')]
#[Attributes\Option('configuration', type: OptionType::InlineMarkup, description: 'Configuration related to the file')]
#[Attributes\Option('command', type: OptionType::InlineMarkup, description: 'Command related to the file')]
#[Attributes\Option('noindex', type: OptionType::Boolean, default: false, description: 'Do not index the file definition')]
#[Attributes\Option('shortDescription', description: 'Short description of the file')]
final class Typo3FileDirective extends BaseDirective
{
    public const NAME = 'typo3:file';

    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly AnchorNormalizer $anchorNormalizer,
        GenericLinkProvider $genericLinkProvider,
    ) {
        $genericLinkProvider->addGenericLink(self::NAME, Typo3FileNode::LINK_TYPE, Typo3FileNode::LINK_PREFIX);
    }

    public function createNode(
        DirectiveNode $directiveNode,
        CompilerContextInterface $compilerContext,
    ): Typo3FileNode {
        $directive = $directiveNode->getDirective();
        $filename = $directive->getData();
        $path = $directive->getOptionString('path');
        $language = $directive->getOptionString('language');
        $regex = $directive->getOptionString('regex');
        if ($regex !== '' && @preg_match($regex, '') === false) {
            $this->logger->warning($regex . ' is not a valid regex. ', $compilerContext->getLoggerInformation());
            $regex = '';
        }
        $pathPrefix = '';
        $classicPathPrefix = '';
        $scope = $directive->getOptionString('scope');
        if ($scope === 'extension') {
            $pathPrefix = 'packages/my_extension';
            $classicPathPrefix = 'typo3conf/ext/my_extension';
        }
        if ($scope === 'set') {
            $pathPrefix = 'packages/my_extension/Configuration/Sets/MySet';
            $classicPathPrefix = 'typo3conf/ext/my_extension/Configuration/Sets/MySet';
        }
        $key = $this->anchorNormalizer->reduceAnchor($directive->getOptionString('scope') . '-' . $this->getPath($directive->getOptionString('composerPath')) . $path . $filename);
        return new Typo3FileNode(
            id: $key,
            fileName: $filename,
            language: $language,
            composerPath: $directive->hasOption('composerPath') ? $this->getPath($directive->getOptionString('composerPath')) : $this->getPath($path),
            composerPathPrefix: $this->getPath($pathPrefix),
            classicPath: $directive->hasOption('classicPath') ? $this->getPath($directive->getOptionString('classicPath')) : $this->getPath($path),
            classicPathPrefix: $this->getPath($classicPathPrefix),
            scope: $directive->getOptionString('scope'),
            regex: $regex,
            configuration: $this->getInlineOptionAsParagraph($directive, 'configuration'),
            command: $this->getInlineOptionAsParagraph($directive, 'command'),
            description: $directiveNode->getChildren(),
            noindex: $directive->getOptionBool('noindex'),
            shortDescription: $directive->getOptionString('shortDescription')
        );
    }

    private function getInlineOptionAsParagraph(Directive $directive, string $name): ?CollectionNode
    {
        if (!$directive->hasOption($name)) {
            return null;
        }

        $node = $directive->getOption($name)->getNode();
        if ($node === null) {
            return null;
        }

        return new CollectionNode([new ParagraphNode([$node])]);
    }

    private function getPath(string $path): string
    {
        if (trim($path) === '/' || trim($path) === '') {
            return '';
        }
        return trim(trim($path), '/') . '/';
    }
}
