<?php

namespace T3Docs\Typo3DocsTheme\ReferenceResolvers\ObjectsInventory;

class ObjectInventory
{
    /**
     * @var array<string,array<string, DataObject>>
     */
    private array $objects = [];

    public function add(string $group, string $key, DataObject $dataObject): void
    {
        $this->objects[$group] ??= [];
        $this->objects[$group][$key] = $dataObject;
    }

    /**
     * @return DataObject[]
     */
    public function getGroup(string $group): array
    {
        if (!isset($this->objects[$group])) {
            return [];
        }
        return $this->objects[$group];
    }

    public function get(string $group, string $key): ?DataObject
    {
        if (!isset($this->objects[$group])) {
            return null;
        }
        if (!isset($this->objects[$group][$key])) {
            return null;
        }
        return $this->objects[$group][$key];
    }
    public function has(string $group, string $key): bool
    {
        if (!isset($this->objects[$group])) {
            return false;
        }
        if (!isset($this->objects[$group][$key])) {
            return false;
        }
        return true;
    }
}
