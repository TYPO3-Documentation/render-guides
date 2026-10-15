<?php

declare(strict_types=1);

namespace T3Docs\Typo3Api\Json\Twig;

use phpDocumentor\Descriptor\ClassDescriptor;
use phpDocumentor\Descriptor\Descriptor;
use phpDocumentor\Descriptor\EnumDescriptor;
use phpDocumentor\Descriptor\Interfaces\ArgumentInterface;
use phpDocumentor\Descriptor\Interfaces\ConstantInterface;
use phpDocumentor\Descriptor\Interfaces\ElementInterface;
use phpDocumentor\Descriptor\Interfaces\EnumCaseInterface;
use phpDocumentor\Descriptor\Interfaces\InterfaceInterface;
use phpDocumentor\Descriptor\Interfaces\MetadataAwareInterface;
use phpDocumentor\Descriptor\Interfaces\MethodInterface;
use phpDocumentor\Descriptor\Interfaces\PropertyInterface;
use phpDocumentor\Descriptor\Interfaces\TraitInterface;
use phpDocumentor\Descriptor\Tag\LinkDescriptor;
use phpDocumentor\Descriptor\Tag\SeeDescriptor;
use phpDocumentor\Descriptor\TagDescriptor;
use phpDocumentor\Descriptor\ValueObjects\Deprecation;
use T3Docs\Typo3Api\Json\Reflection\DocBlockTypes;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * Provides `typo3_api_json(node)`, which returns a class, interface, trait,
 * or enum with its members as an array for `json_encode`.
 *
 * Inherited members are included, with the type that declares them, so that
 * a manual can show the whole public API of a class in one place.
 *
 * The events extension adds which events a method dispatches and which
 * methods dispatch an event, when it is loaded.
 *
 * Descriptions are the text of the docblock: inline tags such as
 * `{@see \Foo}` stay as they are, because links to api.typo3.org would not
 * work in a manual. The manual decides how to render them.
 *
 * A type that phpDocumentor could not read in a docblock is written as it
 * is in the docblock, see DocBlockTypesReducer.
 */
final class ApiJsonExtension extends AbstractExtension
{
    /** Increase this when the format changes in a way that readers must know. */
    public const SCHEMA_VERSION = 1;

    public function getFunctions(): array
    {
        return [
            new TwigFunction('typo3_api_json', $this->type(...)),
        ];
    }

    /** @return array<string, mixed> */
    public function type(ElementInterface $type): array
    {
        $data = [
            'schema' => self::SCHEMA_VERSION,
            'type' => $this->kind($type),
            'fqn' => $this->fqn($type),
            'namespace' => ltrim((string)$type->getNamespace(), '\\'),
            'name' => $type->getName(),
            'file' => $type->getPath(),
            'line' => $type->getLine(),
            'modifiers' => $this->modifiers($type),
            ...$this->docBlock($type),
            'extends' => $this->extends($type),
            'implements' => $this->fqns($type, 'getInterfaces'),
            'uses' => $this->fqns($type, 'getUsedTraits'),
            'constants' => $this->members($type, 'Constants', $this->constant(...)),
            'properties' => $this->members($type, 'Properties', $this->property(...)),
            'methods' => $this->members($type, 'Methods', $this->method(...)),
        ];

        if ($type instanceof EnumDescriptor) {
            $data['backedType'] = $type->getBackedType() === null ? null : (string)$type->getBackedType();
            $data['cases'] = array_values(array_map(
                fn(EnumCaseInterface $case): array => [
                    'name' => $case->getName(),
                    'value' => $this->expression($case->getValue()),
                    ...$this->docBlock($case),
                ],
                $type->getCases()->getAll(),
            ));
        }

        $dispatchedBy = $this->metadata($type, 'typo3_dispatched_by');
        if ($dispatchedBy !== null && method_exists($dispatchedBy, 'getDispatchers')) {
            $data['dispatchedBy'] = array_map($this->fqn(...), $dispatchedBy->getDispatchers());
        }

        return $data;
    }

    /** @return array<string, mixed> */
    private function constant(ConstantInterface $constant): array
    {
        return [
            'name' => $constant->getName(),
            'visibility' => (string)$constant->getVisibility(),
            'modifiers' => $this->modifiers($constant),
            'type' => $constant->getType() === null ? null : (string)$constant->getType(),
            'value' => (string)$this->expression($constant->getValue()),
            ...$this->docBlock($constant),
        ];
    }

    /** @return array<string, mixed> */
    private function property(PropertyInterface $property): array
    {
        // A @var tag that phpDocumentor could not read, kept on the class.
        $docBlockType = $property->getVar()->count() === 0 && $property->getParent() instanceof Descriptor
            ? $this->docBlockTypes($property->getParent())?->getProperty($property->getName())
            : null;

        return [
            'name' => $property->getName(),
            'visibility' => (string)$property->getVisibility(),
            'modifiers' => $this->modifiers($property),
            'type' => $docBlockType['type']
                ?? ($property->getType() === null ? null : (string)$property->getType()),
            'default' => $this->expression($property->getDefault()),
            ...$this->docBlock($property),
        ];
    }

    /** @return array<string, mixed> */
    private function method(MethodInterface $method): array
    {
        $response = $method->getResponse();
        $docBlockTypes = $this->docBlockTypes($method);
        $return = $docBlockTypes?->getReturn();
        $data = [
            'name' => $method->getName(),
            'visibility' => (string)$method->getVisibility(),
            'modifiers' => $this->modifiers($method),
            'parameters' => array_values(array_map(
                fn(ArgumentInterface $argument): array => [
                    'name' => $argument->getName(),
                    'type' => $docBlockTypes?->getParam($argument->getName())['type']
                        ?? ($argument->getType() === null ? null : (string)$argument->getType()),
                    'default' => $this->expression($argument->getDefault()),
                    'variadic' => $argument->isVariadic(),
                    'byReference' => $argument->isByReference(),
                    'description' => $docBlockTypes?->getParam($argument->getName())['description']
                        ?? $this->text($argument->getDescription()),
                ],
                $method->getArguments()->getAll(),
            )),
            'returns' => [
                'type' => $return['type'] ?? ($response->getType() === null ? null : (string)$response->getType()),
                'description' => $return['description'] ?? $this->text($response->getDescription()),
            ],
            ...$this->docBlock($method),
        ];

        $events = $this->metadata($method, 'typo3_dispatched_events');
        if ($events !== null && method_exists($events, 'getEvents')) {
            $data['dispatches'] = array_map($this->fqn(...), $events->getEvents());
        }

        return $data;
    }

    /**
     * The summary, the description, and the tags that decide whether a
     * manual should show an element at all.
     *
     * @return array<string, mixed>
     */
    private function docBlock(Descriptor $element): array
    {
        $data = [
            'summary' => method_exists($element, 'getSummary') ? $element->getSummary() : '',
            'description' => method_exists($element, 'getDescription') ? $this->text($element->getDescription()) : '',
            'deprecated' => null,
            'internal' => null,
        ];

        if (method_exists($element, 'isDeprecated') && $element->isDeprecated()) {
            $data['deprecated'] = trim(implode("\n\n", array_map(
                fn(Deprecation $deprecation): string => $this->text($deprecation->getDescription()),
                $element->getDeprecations(),
            )));
        }

        if (method_exists($element, 'getTags') && $element->getTags()->fetch('internal') !== null) {
            $data['internal'] = trim(implode("\n\n", array_map(
                fn(TagDescriptor $tag): string => $this->text($tag->getDescription()),
                $element->getTags()->fetch('internal')->getAll(),
            )));
        }

        return $data;
    }

    /**
     * The text of a description, with its inline tags written back as they
     * are in the source.
     */
    private function text(mixed $description): string
    {
        if ($description === null || !method_exists($description, 'getBodyTemplate')) {
            return '';
        }

        $tags = [];
        foreach ($description->getTags() as $tag) {
            $tags[] = match (true) {
                $tag instanceof SeeDescriptor => sprintf(
                    '{@see %s%s}',
                    (string)$tag->getReference(),
                    $tag->getDescription()->isEmpty() ? '' : ' ' . $this->text($tag->getDescription()),
                ),
                $tag instanceof LinkDescriptor => sprintf(
                    '{@link %s%s}',
                    $tag->getLink(),
                    $tag->getDescription()->isEmpty() ? '' : ' ' . $this->text($tag->getDescription()),
                ),
                $tag instanceof TagDescriptor => sprintf('{@%s %s}', $tag->getName(), $this->text($tag->getDescription())),
                default => (string)$tag,
            };
        }

        return trim(vsprintf($description->getBodyTemplate(), $tags));
    }

    /**
     * The own members of a type, followed by the inherited ones that it does
     * not override. The constants of a class include those of its
     * interfaces.
     *
     * @param 'Constants'|'Properties'|'Methods' $kind
     * @return list<array<string, mixed>>
     */
    private function members(ElementInterface $type, string $kind, callable $describe): array
    {
        $members = [];
        foreach (['get' . $kind, 'getInherited' . $kind] as $getter) {
            if (!method_exists($type, $getter)) {
                continue;
            }

            foreach ($type->$getter() as $member) {
                $members[$member->getName()] ??= [
                    ...$describe($member),
                    'declaredIn' => $this->fqn($member->getParent()),
                ];
            }
        }

        // A class has the constants of its interfaces, too.
        if ($kind === 'Constants' && !$type instanceof InterfaceInterface && method_exists($type, 'getInterfacesIncludingInherited')) {
            foreach ($type->getInterfacesIncludingInherited() as $interface) {
                if ($interface instanceof InterfaceInterface) {
                    foreach ($this->members($interface, 'Constants', $describe) as $constant) {
                        $members[$constant['name']] ??= $constant;
                    }
                }
            }
        }

        return array_values($members);
    }

    /**
     * The PHP code of a default value or a constant. The printer of
     * phpDocumentor writes \\self::FOO, which is not valid PHP.
     */
    private function expression(?object $expression): ?string
    {
        if ($expression === null) {
            return null;
        }

        return (string)preg_replace('/\\\\(self|static|parent)::/', '$1::', (string)$expression);
    }

    /** @return list<string> */
    private function modifiers(Descriptor $element): array
    {
        $modifiers = [];
        foreach (['isAbstract' => 'abstract', 'isFinal' => 'final', 'isStatic' => 'static', 'isReadOnly' => 'readonly'] as $method => $modifier) {
            if (method_exists($element, $method) && $element->$method()) {
                $modifiers[] = $modifier;
            }
        }

        return $modifiers;
    }

    /** @return string|list<string>|null */
    private function extends(ElementInterface $type): string|array|null
    {
        if ($type instanceof InterfaceInterface) {
            return $this->fqns($type, 'getParent');
        }

        if (!$type instanceof ClassDescriptor || $type->getParent() === null) {
            return null;
        }

        return $this->fqn($type->getParent());
    }

    /** @return list<string> */
    private function fqns(ElementInterface $type, string $getter): array
    {
        if (!method_exists($type, $getter)) {
            return [];
        }

        $fqns = [];
        foreach ($type->$getter() as $element) {
            $fqns[] = $this->fqn($element);
        }

        return $fqns;
    }

    /** The name of an element, which is a descriptor or an Fqsen for one the API does not document. */
    private function fqn(mixed $element): string
    {
        if ($element instanceof ElementInterface) {
            $element = $element->getFullyQualifiedStructuralElementName();
        }

        return ltrim((string)$element, '\\');
    }

    private function kind(ElementInterface $type): string
    {
        return match (true) {
            $type instanceof InterfaceInterface => 'interface',
            $type instanceof TraitInterface => 'trait',
            $type instanceof EnumDescriptor => 'enum',
            default => 'class',
        };
    }

    private function docBlockTypes(Descriptor $element): ?DocBlockTypes
    {
        $types = $this->metadata($element, DocBlockTypes::KEY);

        return $types instanceof DocBlockTypes ? $types : null;
    }

    private function metadata(Descriptor $element, string $key): ?object
    {
        if (!$element instanceof MetadataAwareInterface) {
            return null;
        }

        return $element->getMetadata()[$key] ?? null;
    }
}
