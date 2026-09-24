<?php

namespace T3Docs\Typo3DocsTheme\ReferenceResolvers\ObjectsInventory;

use T3Docs\Typo3DocsTheme\Nodes\Typo3FileNode;

use function is_string;

class FileObject implements DataObject
{
    public const KEY = 'file';

    /**
     * The fields that describe a file, in the order "files.json" lists them.
     * @see \T3Docs\Typo3DocsTheme\Renderer\FilesJsonRenderer
     */
    private const FIELDS = [
        'id',
        'fileName',
        'language',
        'scope',
        'composerPath',
        'composerPathPrefix',
        'classicPath',
        'classicPathPrefix',
        'regex',
        'shortDescription',
    ];

    /**
     * @param string $url where another manual defines the file: set only for
     *     a definition read from that manual's "files.json". A file defined in
     *     the manual being rendered is linked through its link target instead.
     */
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
        public string $url = '',
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

    /**
     * One entry of another manual's "files.json", or null when it lacks
     * something a file needs to be matched and linked.
     *
     * @param array<mixed> $entry
     */
    public static function fromArray(array $entry, string $url): ?FileObject
    {
        $fields = [];
        foreach (self::FIELDS as $field) {
            $value = $entry[$field] ?? '';
            if (!is_string($value)) {
                return null;
            }
            $fields[$field] = $value;
        }
        if ($fields['id'] === '' || $fields['fileName'] === '' || $url === '') {
            return null;
        }

        return new FileObject(...$fields, url: $url);
    }

    /** @return array<string, string> */
    public function toArray(): array
    {
        $entry = [];
        foreach (self::FIELDS as $field) {
            $entry[$field] = $this->{$field};
        }

        return $entry;
    }

    /** Whether a ":file:" role naming $fileLink means this file. */
    public function matchesId(string $fileLink): bool
    {
        return $this->id === $fileLink;
    }

    public function matchesRegex(string $fileLink): bool
    {
        // A definition from another manual brings its own regex, which this
        // render has never validated: an invalid one matches nothing.
        return $this->regex !== '' && @preg_match($this->regex, $fileLink) === 1;
    }
}
