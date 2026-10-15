<?php

declare(strict_types=1);

namespace T3Docs\Typo3Api\Json\Reflection;

use phpDocumentor\Reflection\Metadata\Metadata;

/**
 * The types of the `@return`, `@param`, and `@var` tags as they are written
 * in the docblock: of a method or function the tags that phpDocumentor could
 * not read, of a class the `@var` tags of its properties.
 */
final class DocBlockTypes implements Metadata
{
    public const KEY = 'typo3_docblock_types';

    /** @var array{type: string, description: string}|null */
    private ?array $return = null;

    /** @var array<string, array{type: string, description: string}> indexed by the name without `$` */
    private array $params = [];

    /** @var array<string, array{type: string, description: string}> indexed by the name without `$` */
    private array $properties = [];

    public function key(): string
    {
        return self::KEY;
    }

    public function isEmpty(): bool
    {
        return $this->return === null && $this->params === [] && $this->properties === [];
    }

    public function setReturn(string $type, string $description): void
    {
        $this->return = ['type' => $type, 'description' => $description];
    }

    public function addProperty(string $name, string $type, string $description): void
    {
        $this->properties[$name] = ['type' => $type, 'description' => $description];
    }

    public function addParam(string $name, string $type, string $description): void
    {
        $this->params[$name] = ['type' => $type, 'description' => $description];
    }

    /** @return array{type: string, description: string}|null */
    public function getReturn(): ?array
    {
        return $this->return;
    }

    /** @return array{type: string, description: string}|null */
    public function getProperty(string $name): ?array
    {
        return $this->properties[$name] ?? null;
    }

    /** @return array{type: string, description: string}|null */
    public function getParam(string $name): ?array
    {
        return $this->params[$name] ?? null;
    }
}
