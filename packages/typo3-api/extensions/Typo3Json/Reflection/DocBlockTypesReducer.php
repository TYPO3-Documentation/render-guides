<?php

declare(strict_types=1);

namespace T3Docs\Typo3Api\Json\Reflection;

use phpDocumentor\Reflection\DocBlock\Tags\Param;
use phpDocumentor\Reflection\DocBlock\Tags\Return_;
use phpDocumentor\Reflection\Metadata\MetaDataContainer;
use phpDocumentor\Reflection\Php\Factory\ContextStack;
use phpDocumentor\Reflection\Php\Factory\Reducer\Reducer;
use phpDocumentor\Reflection\Php\Class_;
use phpDocumentor\Reflection\Php\Enum_;
use phpDocumentor\Reflection\Php\Function_;
use phpDocumentor\Reflection\Php\Interface_;
use phpDocumentor\Reflection\Php\Method;
use phpDocumentor\Reflection\Php\Trait_;
use phpDocumentor\Reflection\Php\StrategyContainer;
use PhpParser\Node;
use PhpParser\Node\Stmt\ClassLike;
use PhpParser\Node\Stmt\Property;

/**
 * Keeps the `@return`, `@param`, and `@var` tags whose type phpDocumentor
 * could not read.
 *
 * phpDocumentor 3 reads types with phpdocumentor/type-resolver 1, which
 * accepts few key types in `array<K, V>`: a type alias such as
 * `@phpstan-type LanguageRef -1|0|positive-int`, a literal such as
 * `0|positive-int`, or a union of more than two types makes the whole tag
 * invalid. phpDocumentor then drops the tag, with its description, and
 * shows the type of the parent or the native type, such as `array`.
 * type-resolver 2 reads these types.
 *
 * The invalid tag has lost its name and is rewritten, so the reducer reads
 * the tags from the doc comment of the node. A tag counts as unread when the
 * docblock of phpDocumentor has no valid tag of that name, or for `@param`
 * no valid tag of that variable. The type is kept as it is written, so a
 * class name in it is not resolved.
 *
 * phpDocumentor does not pass the metadata of a property on, so the `@var`
 * tags of the properties are kept on their class. The JSON uses one when the
 * property has no valid `@var` tag.
 */
final class DocBlockTypesReducer implements Reducer
{
    public function reduce(
        ContextStack $context,
        object $object,
        StrategyContainer $strategies,
        object|null $carry,
    ): object|null {
        if (!$carry instanceof MetaDataContainer || !$object instanceof Node) {
            return $carry;
        }

        $types = match (true) {
            $carry instanceof Method, $carry instanceof Function_ => $this->functionTypes($object, $carry),
            $carry instanceof Class_, $carry instanceof Trait_, $carry instanceof Enum_, $carry instanceof Interface_
                => $object instanceof ClassLike ? $this->propertyTypes($object) : null,
            default => null,
        };

        if ($types !== null && !$types->isEmpty()) {
            $carry->addMetadata($types);
        }

        return $carry;
    }

    private function functionTypes(Node $node, Method|Function_ $function): ?DocBlockTypes
    {
        $docComment = $node->getDocComment();
        if ($docComment === null || $function->getDocBlock() === null) {
            return null;
        }

        $read = [];
        foreach ($function->getDocBlock()->getTags() as $tag) {
            $read[] = match (true) {
                $tag instanceof Return_ => 'return',
                $tag instanceof Param => 'param $' . $tag->getVariableName(),
                default => null,
            };
        }

        $types = new DocBlockTypes();
        foreach (self::tags($docComment->getText()) as [$name, $body]) {
            [$type, $rest] = self::splitType($body);
            if ($type === '' || str_starts_with($type, '$')) {
                continue;
            }

            if ($name === 'return' && !in_array('return', $read, true)) {
                $types->setReturn($type, $rest);
            } elseif ($name === 'param'
                && preg_match('/^(?:\.\.\.)?&?\$(\w+)\s*(.*)$/s', $rest, $matches) === 1
                && !in_array('param $' . $matches[1], $read, true)
            ) {
                $types->addParam($matches[1], $type, trim($matches[2]));
            }
        }

        return $types;
    }

    private function propertyTypes(ClassLike $class): DocBlockTypes
    {
        $types = new DocBlockTypes();
        foreach ($class->stmts as $statement) {
            $docComment = $statement instanceof Property ? $statement->getDocComment() : null;
            if ($docComment === null) {
                continue;
            }

            foreach (self::tags($docComment->getText()) as [$name, $body]) {
                [$type, $rest] = self::splitType($body);
                if ($name !== 'var' || $type === '' || str_starts_with($type, '$')) {
                    continue;
                }

                foreach ($statement->props as $property) {
                    $types->addProperty($property->name->toString(), $type, self::withoutVariable($rest));
                }
            }
        }

        return $types;
    }

    /**
     * Splits the body of a tag into the type and the rest. The type ends at
     * the first space outside of brackets, because `array<int, string>`
     * contains one.
     *
     * @return array{string, string}
     */
    public static function splitType(string $body): array
    {
        $body = trim($body);
        $depth = 0;
        $length = strlen($body);
        for ($i = 0; $i < $length; $i++) {
            $character = $body[$i];
            if (str_contains('<({[', $character)) {
                $depth++;
            } elseif (str_contains('>)}]', $character) && $depth > 0) {
                $depth--;
            } elseif ($depth === 0 && ctype_space($character)) {
                // A space before or after | or & is still part of the type.
                $before = rtrim(substr($body, 0, $i));
                $after = ltrim(substr($body, $i));
                if (!str_ends_with($before, '|') && !str_ends_with($before, '&')
                    && !str_starts_with($after, '|') && !str_starts_with($after, '&')
                ) {
                    return [$before, $after];
                }
            }
        }

        return [$body, ''];
    }

    /**
     * The tags of a doc comment as name and body. A body continues on the
     * following lines up to the next tag or an empty line.
     *
     * @return list<array{string, string}>
     */
    public static function tags(string $docComment): array
    {
        $tags = [];
        $lines = preg_split('/\R/', (string)preg_replace('#^\s*/\*\*|\*/\s*$#', '', $docComment)) ?: [];
        $current = null;
        foreach ($lines as $line) {
            $line = trim((string)preg_replace('/^\s*\*?/', '', $line));
            if (preg_match('/^@([\w-]+)(?:\s+(.*))?$/s', $line, $matches) === 1) {
                $tags[] = [$matches[1], $matches[2] ?? ''];
                $current = array_key_last($tags);
            } elseif ($line === '') {
                $current = null;
            } elseif ($current !== null) {
                $tags[$current][1] = trim($tags[$current][1] . ' ' . $line);
            }
        }

        return $tags;
    }

    private static function withoutVariable(string $rest): string
    {
        return trim((string)preg_replace('/^\$\w+/', '', $rest));
    }
}
