<?php

namespace T3Docs\Typo3DocsTheme\ReferenceResolvers\ObjectsInventory;
class ObjectInventory
{
    private array $objects = [];

    protected function add(string $group, string $key, DataObject $dataObject): void
    {
        $this->objects[$group] =  $this->objects[$group]??[];
        $this->objects[$group][$key] = $dataObject;
    }

    protected function get(string $group, string $key): ?DataObject
    {
        if (!isset($this->objects[$group])){
            return null;
        }
        if (!isset($this->objects[$group][$key])){
            return null;
        }
        return $this->objects[$group][$key];
    }
}
