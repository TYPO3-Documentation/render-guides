<?php

declare(strict_types=1);

namespace T3Docs\Typo3Api\Documentation\Reflection;

use phpDocumentor\Reflection\Metadata\Metadata;
use T3Docs\Typo3Api\Documentation\Index\DocumentationIndex;

/**
 * Where the TYPO3 documentation speaks of a class, on the page of the class.
 *
 * @phpstan-import-type Manual from DocumentationIndex
 */
final class Documentation implements Metadata
{
    public const KEY = 'typo3_documentation';

    /** @param list<Manual> $manuals */
    public function __construct(private readonly array $manuals) {}

    public function key(): string
    {
        return self::KEY;
    }

    /** @return list<Manual> */
    public function getManuals(): array
    {
        return $this->manuals;
    }
}
