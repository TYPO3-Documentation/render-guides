<?php

declare(strict_types=1);

namespace T3Docs\Typo3DocsTheme\Nodes;

use phpDocumentor\Guides\Nodes\CollectionNode;
use phpDocumentor\Guides\Nodes\Node;

use function array_values;
use function count;
use function is_array;
use function is_int;

/**
 * For a node that keeps content in properties of its own besides its children,
 * such as the description of a file or a ViewHelper: makes that content
 * children too, after the node's own, so that the compiler reaches it.
 *
 * Since phpDocumentor/guides 1.11 a directive such as "note" or "tabs" is
 * turned into its node while compiling, not while parsing, and the compiler
 * only walks the children of a node. Content outside them kept the directive
 * unprocessed and rendered as plain text.
 *
 * The compiler replaces a child by its position, so a position beyond the
 * node's own children is mapped back to the property, and to the key within
 * it for a list. Such a property cannot be readonly: a replaced child is set
 * on a copy of the node.
 *
 * The templates keep reading the properties, not the children.
 */
trait CompiledPropertiesTrait
{
    /**
     * The properties that hold a node or a list of nodes, in the order their
     * content comes on the page.
     *
     * @return list<string>
     */
    abstract protected function compiledProperties(): array;

    /**
     * Whether an entry of a list is compiled. Content the page does not show
     * stays as parsed: compiled, a section in it would become a section of
     * the page, listed in its contents without being on it.
     */
    protected function compilesItem(string $property, int|string $key): bool
    {
        return true;
    }

    /** @return Node[] */
    public function getChildren(): array
    {
        $children = parent::getChildren();
        foreach ($this->propertySlots() as [, , $node]) {
            $children[] = $node;
        }

        return $children;
    }

    public function replaceNode(int $key, Node $node): static
    {
        $slot = $this->propertySlot($key);
        if ($slot === null) {
            return parent::replaceNode($key, $node);
        }

        [$property, $itemKey] = $slot;
        $result = clone $this;
        if ($itemKey === null) {
            // A property for one node holds a collection of them.
            $result->{$property} = $node instanceof CollectionNode ? $node : new CollectionNode([$node]);
        } else {
            $items = $result->items($property);
            $items[$itemKey] = $node;
            $result->{$property} = $items;
        }

        return $result;
    }

    public function removeNode(int $key): static
    {
        $slot = $this->propertySlot($key);
        if ($slot === null) {
            return parent::removeNode($key);
        }

        [$property, $itemKey] = $slot;
        $result = clone $this;
        if ($itemKey === null) {
            $result->{$property} = null;
        } else {
            $items = $result->items($property);
            unset($items[$itemKey]);
            $result->{$property} = is_int($itemKey) ? array_values($items) : $items;
        }

        return $result;
    }

    /** @return array{string, int|string|null, Node}|null the property and key of the child at a position beyond the node's own */
    private function propertySlot(int $key): ?array
    {
        $own = count(parent::getChildren());
        if ($key < $own) {
            return null;
        }

        return $this->propertySlots()[$key - $own] ?? null;
    }

    /** @return list<array{string, int|string|null, Node}> each property, key and node compiled as a child */
    private function propertySlots(): array
    {
        $slots = [];
        foreach ($this->compiledProperties() as $property) {
            $value = $this->{$property};
            if ($value instanceof Node) {
                $slots[] = [$property, null, $value];
                continue;
            }
            foreach ($this->items($property) as $itemKey => $item) {
                if ($item instanceof Node && $this->compilesItem($property, $itemKey)) {
                    $slots[] = [$property, $itemKey, $item];
                }
            }
        }

        return $slots;
    }

    /** @return array<int|string, mixed> the entries of a property that holds a list, or none */
    private function items(string $property): array
    {
        $value = $this->{$property};

        return is_array($value) ? $value : [];
    }
}
