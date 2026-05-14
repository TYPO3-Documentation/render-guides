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
use phpDocumentor\Guides\Nodes\Node;
use phpDocumentor\Guides\ReferenceResolvers\AnchorNormalizer;
use phpDocumentor\Guides\RestructuredText\Directives\SubDirective;
use phpDocumentor\Guides\RestructuredText\Parser\BlockContext;
use phpDocumentor\Guides\RestructuredText\Parser\Directive;
use phpDocumentor\Guides\RestructuredText\Parser\Productions\Rule;
use phpDocumentor\Guides\RestructuredText\TextRoles\GenericLinkProvider;
use Psr\Log\LoggerInterface;
use T3Docs\Typo3DocsTheme\Nodes\Typo3FileNode;

final class Typo3FileDirective extends SubDirective
{
    public const NAME = 'typo3:file';

    public function __construct(
        Rule $startingRule,
        private readonly LoggerInterface $logger,
        private readonly AnchorNormalizer $anchorNormalizer,
        GenericLinkProvider $genericLinkProvider,
    ) {
        $genericLinkProvider->addGenericLink(self::NAME, Typo3FileNode::LINK_TYPE, Typo3FileNode::LINK_PREFIX);
        parent::__construct($startingRule);
    }

    public function getName(): string
    {
        return self::NAME;
    }

    protected function processSub(
        BlockContext $blockContext,
        CollectionNode $collectionNode,
        Directive $directive,
    ): Node {
        $filename = $directive->getData();
        $path = $directive->getOptionString('path');
        $language = $directive->getOptionString('language');
        $regex = $directive->getOptionString('regex');
        if ($regex !== '' && @preg_match($regex, '') === false) {
            $this->logger->warning($regex . ' is not a valid regex. ', $blockContext->getLoggerInformation());
            $regex = '';
        }
        $configuration = null;
        if ($directive->hasOption('configuration')) {
            $blockContextOfCaption = new BlockContext($blockContext->getDocumentParserContext(), $directive->getOptionString('configuration'));
            $configuration = $this->startingRule->apply($blockContextOfCaption);
        }
        $command = null;
        if ($directive->hasOption('command')) {
            $blockContextOfCaption = new BlockContext($blockContext->getDocumentParserContext(), $directive->getOptionString('command'));
            $command = $this->startingRule->apply($blockContextOfCaption);
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
            configuration: $configuration,
            command: $command,
            description: $collectionNode->getChildren(),
            noindex: $directive->getOptionBool('noindex'),
            shortDescription: $directive->getOptionString('shortDescription')
        );
    }

    private function getPath(string $path): string
    {
        if (trim($path) === '/' || trim($path) === '') {
            return '';
        }
        return trim(trim($path), '/') . '/';
    }
}
