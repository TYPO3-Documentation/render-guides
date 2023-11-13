<?php

declare(strict_types=1);

namespace T3Docs\PhpDomain\PhpDomain;

use T3Docs\PhpDomain\Nodes\FullyQualifiedNameNode;
use T3Docs\PhpDomain\Nodes\PhpNamespaceNode;

class FullyQualifiedNameService
{
    /**
     * @see https://regex101.com/r/j89USB/1
     */
    private const BASE_NAME_PATTERN_REGEX = '/^[a-zA-Z_\x80-\xff][a-zA-Z0-9_\x80-\xff]*$/';

    /**
     * @see https://regex101.com/r/bt7r5S/1
     */
    public const FULL_NAME_PATTERN_REGEX = '/^(.+\\\\)([^\\\\]+)$/';

    public function __construct(
        private readonly NamespaceRepository $namespaceRepository
    ) {}

    /**
     * @param list<string> $matches
     */
    public function isFullyQualifiedName(string $name, array &$matches): bool
    {
        return (bool)preg_match(self::FULL_NAME_PATTERN_REGEX, $name, $matches);
    }
    public function isBaseName(string $name): bool
    {
        return (bool)preg_match(self::BASE_NAME_PATTERN_REGEX, $name);
    }

    public function getFullyQualifiedName(string $name, bool $useCurrentNamespace = false): FullyQualifiedNameNode
    {
        if ($this->isBaseName($name)) {
            if ($useCurrentNamespace) {
                return new FullyQualifiedNameNode($name, $this->namespaceRepository->getCurrentNamespace());
            }
            return new FullyQualifiedNameNode($name, null);
        }
        $matches = [];
        if ($this->isFullyQualifiedName($name, $matches)) {
            $namespace = $matches[1];
            $namespace = trim($namespace, '\\');
            $baseName = $matches[2];
            return new FullyQualifiedNameNode($baseName, new PhpNamespaceNode($namespace));
        }
        throw new \Exception($name . ' is not a valid class or interface name in PHP');
    }
}
