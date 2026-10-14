<?php

declare(strict_types=1);

namespace T3Docs\Typo3Api\Events\Compiler;

use phpDocumentor\Compiler\ApiDocumentation\ApiDocumentationPass;
use phpDocumentor\Descriptor\Collection;
use phpDocumentor\Descriptor\Interfaces\ApiDocumentationSet;
use phpDocumentor\Descriptor\Interfaces\ClassInterface;
use phpDocumentor\Descriptor\Interfaces\ElementInterface;
use phpDocumentor\Descriptor\Interfaces\MetadataAwareInterface;
use phpDocumentor\Pipeline\Attribute\Stage;
use phpDocumentor\Reflection\Fqsen;
use T3Docs\Typo3Api\Events\Reflection\DispatchedBy;
use T3Docs\Typo3Api\Events\Reflection\DispatchedEvents;

/**
 * Turns the events that methods dispatch around: each event class gets the
 * list of the methods and functions that dispatch it.
 *
 * This needs every method of the project, which the reflection of a single
 * method cannot see, so it runs as a compiler pass after the "elements"
 * index is built.
 */
#[Stage(
    'phpdoc.pipeline.api_documentation.compile',
    8000,
    'List the methods that dispatch an event on the page of the event',
)]
final class DispatchedByBuilder extends ApiDocumentationPass
{
    protected function process(ApiDocumentationSet $subject): ApiDocumentationSet
    {
        /** @var array<string, list<Fqsen>> $dispatchers indexed by the event */
        $dispatchers = [];
        /** @var array<string, ClassInterface&MetadataAwareInterface> $classes */
        $classes = [];

        foreach ($subject->getIndexes()->fetch('elements', new Collection()) as $element) {
            if (!$element instanceof ElementInterface || !$element instanceof MetadataAwareInterface) {
                continue;
            }

            if ($element instanceof ClassInterface) {
                $classes[(string)$element->getFullyQualifiedStructuralElementName()] = $element;
            }

            $events = $element->getMetadata()[DispatchedEvents::KEY] ?? null;
            if (!$events instanceof DispatchedEvents) {
                continue;
            }

            foreach ($events->getEvents() as $event) {
                $dispatchers[(string)$event][] = $element->getFullyQualifiedStructuralElementName();
            }
        }

        foreach ($dispatchers as $event => $fqsens) {
            $class = $classes[$event] ?? null;
            if ($class === null) {
                continue;
            }

            usort($fqsens, self::compareShortNames(...));
            $class->setMetadata([
                ...$class->getMetadata(),
                DispatchedBy::KEY => new DispatchedBy($fqsens),
            ]);
        }

        return $subject;
    }

    /**
     * The page shows `Class::method()` without the namespace, so sort by
     * that, and by the full name where two short names are the same.
     */
    private static function compareShortNames(Fqsen $a, Fqsen $b): int
    {
        return strcasecmp(self::shortName($a), self::shortName($b)) ?: strcmp((string)$a, (string)$b);
    }

    private static function shortName(Fqsen $fqsen): string
    {
        $name = (string)$fqsen;
        $separator = strrpos(strstr($name, '::', true) ?: $name, '\\');

        return $separator === false ? $name : substr($name, $separator + 1);
    }
}
