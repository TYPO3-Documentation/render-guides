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
use T3Docs\Typo3DocsTheme\Nodes\Inline\FileInlineNode;
use T3Docs\Typo3DocsTheme\ReferenceResolvers\ObjectsInventory\DataObject;
use T3Docs\Typo3DocsTheme\ReferenceResolvers\ObjectsInventory\FileObject;
use T3Docs\Typo3DocsTheme\ReferenceResolvers\ObjectsInventory\ObjectInventory;

/** @implements NodeTransformer<FileInlineNode> */
final class AttachFileObjectsToFileTextRoleTransformer implements NodeTransformer
{
    public function __construct(
        private readonly ObjectInventory $objectInventory,
    ) {}

    public function enterNode(Node $node, CompilerContextInterface $compilerContext): Node
    {
        if (!$node instanceof FileInlineNode) {
            return $node;
        }
        /** @var DataObject[] $fileObjects */
        $fileObjects = $this->objectInventory->getGroup(FileObject::KEY);
        foreach ($fileObjects as $fileObject) {
            if (!$fileObject instanceof FileObject) {
                continue;
            }
            if ($fileObject->id === $node->getFileLink()) {
                $node->setFileObject($fileObject);
                return $node;
            }
            if (preg_match($fileObject->regex, $node->getFileLink())) {
                $node->setFileObject($fileObject);
                break;
            }
        }
        foreach ($fileObjects as $fileObject) {
            if (!$fileObject instanceof FileObject) {
                continue;
            }
            if ($fileObject->regex === '') {
                continue;
            }
            if (preg_match($fileObject->regex, $node->getFileLink())) {
                $node->setFileObject($fileObject);
                break;
            }
        }

        return $node;
    }

    public function leaveNode(Node $node, CompilerContextInterface $compilerContext): Node
    {
        return $node;
    }

    public function supports(Node $node): bool
    {
        return $node instanceof FileInlineNode;
    }

    public function getPriority(): int
    {
        // After CollectFileObjectsTransformer
        return 2000;
    }
}
