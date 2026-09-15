<?php

declare(strict_types=1);

namespace T3Docs\Typo3DocsTheme\Directives;

use phpDocumentor\Guides\RestructuredText\Directives\ActionDirective;
use phpDocumentor\Guides\RestructuredText\Parser\BlockContext;
use phpDocumentor\Guides\RestructuredText\Parser\Directive;
use T3Docs\Typo3DocsTheme\Nodes\Metadata\IndexEntriesNode;

use function array_filter;
use function array_map;
use function array_values;
use function explode;
use function trim;

/**
 * Keeps the terms of an ".. index::" directive instead of discarding them.
 *
 * The library parses the directive and throws it away: its IndexDirective
 * extends SubDirective without overriding processSub(), so process() returns
 * null and no node reaches the document. Nothing renders an index today, so
 * for HTML that costs nothing -- but the TYPO3 Core Changelog tags every one
 * of its 3402 entries this way, and those tags are the only categorisation an
 * entry has. The Markdown output publishes them as front matter.
 *
 * Only the single-line, comma-separated form is read:
 *
 *     ..  index:: Frontend, PHP-API, TypoScript, ext:frontend
 *
 * Sphinx also knows "single:", "pair:", "see:" and a leading "!" for a main
 * entry. The Changelog uses none of them -- all 3402 entries are this one form
 * -- and guessing at the others here would be a second, worse implementation
 * of what phpDocumentor/guides#1358 already does properly, including a
 * genindex page. When that lands, this directive and IndexEntriesNode go, and
 * the front matter reads the upstream nodes instead.
 *
 * An ActionDirective rather than a node-producing one: an index entry is
 * invisible, it belongs to the page and not to the place in the text where it
 * was written.
 */
final class IndexEntriesDirective extends ActionDirective
{
    public function getName(): string
    {
        return 'index';
    }

    public function processAction(BlockContext $blockContext, Directive $directive): void
    {
        $terms = array_values(array_filter(
            array_map(trim(...), explode(',', $directive->getData())),
            static fn(string $term): bool => $term !== '',
        ));

        if ($terms === []) {
            return;
        }

        $blockContext->getDocumentParserContext()->getDocument()->addHeaderNode(
            new IndexEntriesNode($terms),
        );
    }
}
