<?php

declare(strict_types=1);

/**
 * This file is part of phpDocumentor.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @link https://phpdoc.org
 */

namespace T3Docs\Typo3DocsTheme\Lint;

/**
 * A lint rule that inspects the raw reStructuredText source of a document,
 * before parsing (see #1157). Source-level rules cover concerns that are lost
 * in the parsed AST, e.g. line length, indentation/whitespace and the authored
 * heading-adornment sequence.
 */
interface SourceLintRule
{
    /**
     * @return list<string> human-readable warning messages (one per finding)
     */
    public function lint(string $contents): array;
}
