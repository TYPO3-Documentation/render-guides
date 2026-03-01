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

namespace T3Docs\Typo3DocsTheme\Compiler\NodeTransformers;

use phpDocumentor\Guides\Compiler\CompilerContextInterface;
use phpDocumentor\Guides\Compiler\NodeTransformer;
use phpDocumentor\Guides\Nodes\Node;
use Psr\Log\LoggerInterface;
use T3Docs\Typo3DocsTheme\Nodes\Typo3FileNode;
use T3Docs\Typo3DocsTheme\ReferenceResolvers\ObjectsInventory\FileObject;
use T3Docs\Typo3DocsTheme\ReferenceResolvers\ObjectsInventory\ObjectInventory;

use function sprintf;

/** @implements NodeTransformer<Typo3FileNode> */
final class CollectFileObjectsTransformer implements NodeTransformer
{
    public function __construct(
        private readonly ObjectInventory $objectInventory,
        private readonly LoggerInterface $logger,
    ) {}

    public function enterNode(Node $node, CompilerContextInterface $compilerContext): Node
    {
        if (!$node instanceof Typo3FileNode) {
            return $node;
        }
        if ($node->isNoindex()) {
            return $node;
        }
        $fileObject = FileObject::fromTypo3Node($node);
        if ($this->objectInventory->has(FileObject::KEY, $node->getId())) {
            $this->logger->warning(sprintf('There is already a file with the name "%s" registered, use option `:name:` to provide a different name or set it to `:noindex:` to exclude it from indexing. ', $node->getName()), $compilerContext->getLoggerInformation());
        }
        $this->objectInventory->add(FileObject::KEY, $node->getId(), $fileObject);

        return $node;
    }

    public function leaveNode(Node $node, CompilerContextInterface $compilerContext): Node
    {
        return $node;
    }

    public function supports(Node $node): bool
    {
        return $node instanceof Typo3FileNode;
    }

    public function getPriority(): int
    {
        // Before AttachFileObjectsToFileTextRoleTransformer
        return 4000;
    }
}
