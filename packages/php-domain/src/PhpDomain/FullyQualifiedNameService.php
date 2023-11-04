<?php

declare(strict_types=1);

namespace T3Docs\PhpDomain\PhpDomain;

use T3Docs\PhpDomain\Nodes\FullyQualifiedNameNode;
use T3Docs\PhpDomain\Nodes\PhpNamespaceNode;

class FullyQualifiedNameService
{
    private const BASE_NAME_PATTERN = '/^[a-zA-Z_\x80-\xff][a-zA-Z0-9_\x80-\xff]*$/';
    public const FULL_NAME_PATTERN = '/^(.+\\\\)([^\\\\]+)$/';

    /**
     * @param list<string> $matches
     */
    public function isFullyQualifiedName(string $name, array &$matches): bool
    {
        return (bool)preg_match(self::FULL_NAME_PATTERN, $name, $matches);
    }
    public function isBaseName(string $name): bool
    {
        return (bool)preg_match(self::BASE_NAME_PATTERN, $name);
    }

    public function getFullyQualifiedName(string $name): FullyQualifiedNameNode
    {
        if ($this->isBaseName($name)) {
            return new FullyQualifiedNameNode($name, null);
        }
        $matches = [];
        if ($this->isFullyQualifiedName($name, $matches)) {
            $namespace = $matches[1];
            $baseName = $matches[2];
            return new FullyQualifiedNameNode($baseName, new PhpNamespaceNode($namespace));
        }
        throw new \Exception($name . ' is not a valid class or interface name in PHP');
    }
}
