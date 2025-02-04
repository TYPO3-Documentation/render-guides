<?php

namespace T3Docs\Typo3DocsTheme\ReferenceResolvers\ObjectsInventory;

use T3Docs\Typo3DocsTheme\Nodes\Typo3FileNode;

class FileObject implements DataObject
{
    public const KEY = 'file';
    public function __construct(
        public string $fileName,
        public string $language,
        public string $composerPath = '',
        public string $composerPathPrefix = '',
        public string $classicPath = '',
        public string $classicPathPrefix = '',
        public string $scope = '',
        public string $regex = '',
        public string $shortDescription = '',
        public string $id = '',
    ) {}

    public static function fromTypo3Node(Typo3FileNode $node): FileObject
    {
        return new FileObject(
            $node->getFileName(),
            $node->getLanguage(),
            $node->getComposerPath(),
            $node->getComposerPathPrefix(),
            $node->getClassicPath(),
            $node->getClassicPathPrefix(),
            $node->getScope(),
            $node->getRegex(),
            $node->getShortDescription(),
            $node->getId(),
        );
    }
}
