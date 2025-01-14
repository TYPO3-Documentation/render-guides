<?php

namespace T3Docs\Typo3DocsTheme\ReferenceResolvers\ObjectsInventory;

class FileObject implements DataObject
{
    public function __construct(
        public string $fileName,
        public string $language,
        public string $path,
        public string $composerPath = '',
        public string $composerPathPrefix = '',
        public string $classicPath = '',
        public string $classicPathPrefix = '',
        public string $scope = '',
    ) {}
}
