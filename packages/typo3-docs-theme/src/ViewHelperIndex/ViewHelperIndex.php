<?php

declare(strict_types=1);

namespace T3Docs\Typo3DocsTheme\ViewHelperIndex;

use phpDocumentor\Guides\Nodes\CompoundNode;
use phpDocumentor\Guides\Nodes\DocumentNode;
use phpDocumentor\Guides\Nodes\Node;
use phpDocumentor\Guides\Nodes\ParagraphNode;
use phpDocumentor\Guides\ReferenceResolvers\AnchorNormalizer;
use T3Docs\Typo3DocsTheme\Nodes\ViewHelperNode;
use T3Docs\Typo3DocsTheme\Permalinks\Permalinks;

use function is_string;
use function preg_replace;
use function trim;

/**
 * Every ViewHelper a manual documents with ".. typo3:viewhelper::", with
 * what the file describing it says and where the manual shows it.
 *
 * A ViewHelper is keyed by the name a template writes, "f:format.html": the
 * prefix of its namespace, from the describing file, and its tag name. That
 * is what a ":fluid:" role names, and what a reader looks for.
 *
 * @phpstan-type Argument array{type: string, required: bool, default?: string, description?: string, anchor: string, permalink?: string}
 * @phpstan-type Entry array{class: string, namespace: string, xmlNamespace: string, summary?: string, documentation?: string, docTags?: array<string>, allowsArbitraryArguments: bool, source?: string, arguments: array<string, Argument>, path: string, anchor: string, permalink?: string}
 */
final class ViewHelperIndex
{
    public function __construct(
        private readonly AnchorNormalizer $anchorNormalizer,
        private readonly Permalinks $permalinks,
    ) {}

    /**
     * @param iterable<DocumentNode> $documents
     * @return array<string, Entry> each ViewHelper by its full name, in the order of the documents
     */
    public function of(iterable $documents, ?string $projectVersion): array
    {
        $viewHelpers = [];
        foreach ($documents as $document) {
            $this->walk($document, $document->getFilePath(), $projectVersion, $viewHelpers);
        }

        return $viewHelpers;
    }

    /** @param array<string, Entry> $viewHelpers */
    private function walk(Node $node, string $path, ?string $projectVersion, array &$viewHelpers): void
    {
        if ($node instanceof ViewHelperNode) {
            // A ViewHelper shown twice is listed where it is indexed: the
            // page a ":noindex:" one stands on is not where it is documented.
            if (!$node->isNoindex() && !isset($viewHelpers[$node->getFullName()])) {
                $viewHelpers[$node->getFullName()] = $this->entry($node, $path, $projectVersion);
            }
            return;
        }
        if (!$node instanceof CompoundNode) {
            return;
        }
        foreach ($node->getChildren() as $child) {
            if ($child instanceof Node) {
                $this->walk($child, $path, $projectVersion, $viewHelpers);
            }
        }
    }

    /** @return Entry */
    private function entry(ViewHelperNode $node, string $path, ?string $projectVersion): array
    {
        $entry = [
            'class' => $node->getClassName(),
            'namespace' => $node->getNamespace(),
            'xmlNamespace' => $node->getXmlNamespace(),
        ];
        $summary = $this->summary($node);
        if ($summary !== '') {
            $entry['summary'] = $summary;
        }
        if (trim($node->getRawDocumentation()) !== '') {
            $entry['documentation'] = trim($node->getRawDocumentation());
        }
        if ($node->getDocTags() !== []) {
            $entry['docTags'] = $node->getDocTags();
        }
        $entry['allowsArbitraryArguments'] = $node->isAllowsArbitraryArguments();
        if ($node->getGitHubLink() !== '') {
            $entry['source'] = $node->getGitHubLink();
        }

        $arguments = [];
        foreach ($node->getArguments() as $name => $argument) {
            $anchor = $this->anchorNormalizer->reduceAnchor($argument->getAnchor());
            $data = ['type' => $argument->getType(), 'required' => $argument->isRequired()];
            $default = $argument->getDefaultValue();
            if (is_string($default)) {
                $data['default'] = $default;
            }
            if (trim($argument->getDescription()) !== '') {
                $data['description'] = trim($argument->getDescription());
            }
            $data['anchor'] = $anchor;
            $permalink = $this->permalinks->forAnchor($anchor, $projectVersion);
            if ($permalink !== '') {
                $data['permalink'] = $permalink;
            }
            $arguments[(string) $name] = $data;
        }
        $entry['arguments'] = $arguments;

        $anchor = $this->anchorNormalizer->reduceAnchor($node->getAnchor());
        $entry['path'] = $path;
        $entry['anchor'] = $anchor;
        // A manual without an interlink shortcode has no permalinks.
        $permalink = $this->permalinks->forAnchor($anchor, $projectVersion);
        if ($permalink !== '') {
            $entry['permalink'] = $permalink;
        }

        return $entry;
    }

    /** The first paragraph of the description, as plain text. */
    public function summary(ViewHelperNode $node): string
    {
        foreach ($node->getDescription() as $child) {
            if ($child instanceof ParagraphNode) {
                return trim((string) preg_replace('/\s+/', ' ', $this->plain($child)));
            }
        }

        return '';
    }

    private function plain(Node $node): string
    {
        if (!$node instanceof CompoundNode) {
            $value = $node->getValue();

            return is_string($value) ? $value : '';
        }

        $text = '';
        foreach ($node->getChildren() as $child) {
            $text .= $child instanceof Node ? $this->plain($child) : '';
        }

        return $text;
    }
}
