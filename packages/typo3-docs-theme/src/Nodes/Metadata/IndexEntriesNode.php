<?php

declare(strict_types=1);

namespace T3Docs\Typo3DocsTheme\Nodes\Metadata;

use phpDocumentor\Guides\Nodes\Metadata\MetadataNode;

use function array_values;

/**
 * The terms of an ".. index::" directive, kept for the page they appear on.
 *
 * The rendered page does not show them -- an index directive is invisible by
 * design -- but they are the only categorisation a TYPO3 Core Changelog entry
 * carries, and the Markdown output publishes them as front matter.
 *
 * Interim: phpDocumentor/guides#1358 collects index entries properly, with all
 * four Sphinx forms and a genindex page. Until that lands, this holds the one
 * form the Changelog uses. See IndexEntriesDirective.
 */
final class IndexEntriesNode extends MetadataNode
{
    /** @param list<string> $terms */
    public function __construct(private readonly array $terms)
    {
        parent::__construct('');
    }

    /** @return list<string> */
    public function getTerms(): array
    {
        return array_values($this->terms);
    }
}
