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
use T3Docs\Typo3DocsTheme\ReferenceResolvers\ObjectsInventory\ExternalFileObjects;
use T3Docs\Typo3DocsTheme\ReferenceResolvers\ObjectsInventory\FileObject;
use T3Docs\Typo3DocsTheme\ReferenceResolvers\ObjectsInventory\ObjectInventory;

use function array_filter;

/** @implements NodeTransformer<FileInlineNode> */
final class AttachFileObjectsToFileTextRoleTransformer implements NodeTransformer
{
    public function __construct(
        private readonly ObjectInventory $objectInventory,
        private readonly ExternalFileObjects $externalFileObjects,
    ) {}

    public function enterNode(Node $node, CompilerContextInterface $compilerContext): Node
    {
        if (!$node instanceof FileInlineNode) {
            return $node;
        }
        $fileObjects = array_filter(
            $this->objectInventory->getGroup(FileObject::KEY),
            static fn(DataObject $fileObject): bool => $fileObject instanceof FileObject,
        );
        // A file the manual defines itself wins; only then is it looked up in
        // TYPO3 Explained, where the official manuals define their files.
        $node->setFileObject(
            $this->match($fileObjects, $node->getFileLink())
            ?? $this->match($this->externalFileObjects->all(), $node->getFileLink()),
        );

        return $node;
    }

    /**
     * The file named by its id, or else the first whose regex matches.
     *
     * @param FileObject[] $fileObjects
     */
    private function match(array $fileObjects, string $fileLink): ?FileObject
    {
        foreach ($fileObjects as $fileObject) {
            if ($fileObject->matchesId($fileLink)) {
                return $fileObject;
            }
        }
        foreach ($fileObjects as $fileObject) {
            if ($fileObject->matchesRegex($fileLink)) {
                return $fileObject;
            }
        }

        return null;
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
