<?php

declare(strict_types=1);

namespace T3Docs\Typo3DocsTheme\ManualsIndex;

use phpDocumentor\Guides\Nodes\CompoundNode;
use phpDocumentor\Guides\Nodes\Inline\CrossReferenceNode;
use phpDocumentor\Guides\Nodes\Inline\AbstractLinkInlineNode;
use phpDocumentor\Guides\Nodes\ListItemNode;
use phpDocumentor\Guides\Nodes\ListNode;
use phpDocumentor\Guides\Nodes\Node;
use phpDocumentor\Guides\ReferenceResolvers\DelegatingReferenceResolver;
use phpDocumentor\Guides\ReferenceResolvers\Messages;
use phpDocumentor\Guides\RenderContext;
use T3Docs\Typo3DocsTheme\Inventory\InterlinkParserInterface;
use T3Docs\Typo3DocsTheme\Nodes\MainMenuJsonNode;

use function in_array;
use function is_string;
use function parse_url;
use function preg_match;
use function strtolower;
use function trim;

use const PHP_URL_HOST;
use const PHP_URL_PATH;

/**
 * The manuals the main menu of docs.typo3.org lists, with their versions.
 *
 * Every manual publishes machine-readable indexes of its own beside its pages
 * -- toc.json, objects.inv.json, classes.json and more -- but nothing said
 * which manuals there are. The docs homepage knows: its main menu links every
 * official manual in every version it offers, and it does so with interlink
 * references, whose domain is the manual's interlink shortcode. So the menu
 * gives each manual's shortcode, its name, and the address of each version,
 * which is where its indexes are.
 *
 * A menu entry may point into a manual rather than at its start, such as
 * "Administration" into TYPO3 Explained. A manual is listed once, by its
 * shortcode, and named by the entry that points at its start page.
 *
 * @phpstan-type Version array{version: string, base: string}
 * @phpstan-type Manual array{name: string, group: string, versions: list<Version>}
 */
final class ManualsIndex
{
    /** The targets of a manual's start page: its label, or its index document. */
    private const START = ['start', 'index'];

    /** @var array<string, Manual> */
    private array $manuals = [];

    /** @var array<string, true> the shortcodes named by their start page already */
    private array $named = [];

    public function __construct(
        private readonly DelegatingReferenceResolver $referenceResolver,
        private readonly InterlinkParserInterface $interlinkParser,
    ) {}

    /** @return array<string, Manual> each manual by its interlink shortcode, in the order of the menu */
    public function of(MainMenuJsonNode $menu, RenderContext $renderContext): array
    {
        $this->manuals = [];
        $this->named = [];
        foreach ($menu->getChildren() as $child) {
            $this->groups($child, $renderContext);
        }

        return $this->manuals;
    }

    private function groups(Node $node, RenderContext $renderContext): void
    {
        if (!$node instanceof ListNode) {
            if ($node instanceof CompoundNode) {
                foreach ($node->getChildren() as $child) {
                    $this->groups($child, $renderContext);
                }
            }
            return;
        }

        foreach ($node->getChildren() as $item) {
            if (!$item instanceof ListItemNode) {
                continue;
            }
            $group = $this->link($item)?->toString() ?? '';
            foreach ($item->getChildren() as $child) {
                if ($child instanceof ListNode) {
                    $this->entries($child, trim($group), $renderContext);
                }
            }
        }
    }

    /** A manual, or a part of one, and the versions listed below it. */
    private function entries(ListNode $list, string $group, RenderContext $renderContext): void
    {
        foreach ($list->getChildren() as $item) {
            if (!$item instanceof ListItemNode) {
                continue;
            }
            $link = $this->link($item);
            $shortcode = $link === null ? '' : $this->shortcode($link);
            if ($link === null || $shortcode === '') {
                continue;
            }

            $versions = [];
            foreach ($item->getChildren() as $child) {
                if (!$child instanceof ListNode) {
                    continue;
                }
                foreach ($child->getChildren() as $versionItem) {
                    $versionLink = $versionItem instanceof ListItemNode ? $this->link($versionItem) : null;
                    if ($versionLink !== null && $this->shortcode($versionLink) === $shortcode) {
                        $versions[] = $versionLink;
                    }
                }
            }
            // A manual the menu offers in one version only links it directly.
            if ($versions === []) {
                $versions = [$link];
            }

            $this->add($shortcode, $link, $group, $versions, $renderContext);
        }
    }

    /** @param list<AbstractLinkInlineNode> $versions */
    private function add(string $shortcode, AbstractLinkInlineNode $link, string $group, array $versions, RenderContext $renderContext): void
    {
        // A reference without a link text has its title only once resolved.
        $this->referenceResolver->resolve($link, $renderContext, new Messages());
        $isStart = in_array(strtolower($link->getTargetReference()), self::START, true);
        $manual = $this->manuals[$shortcode] ?? ['name' => '', 'group' => '', 'versions' => []];
        if (!isset($this->named[$shortcode]) && ($isStart || $manual['name'] === '')) {
            $manual['name'] = trim($link->toString());
            $manual['group'] = $group;
            if ($isStart) {
                $this->named[$shortcode] = true;
            }
        }

        foreach ($versions as $versionLink) {
            $version = $this->version($versionLink, $renderContext);
            if ($version === null) {
                continue;
            }
            foreach ($manual['versions'] as $known) {
                if ($known['base'] === $version['base']) {
                    continue 2;
                }
            }
            $manual['versions'][] = $version;
        }

        $this->manuals[$shortcode] = $manual;
    }

    /** @return Version|null */
    private function version(AbstractLinkInlineNode $link, RenderContext $renderContext): ?array
    {
        $this->referenceResolver->resolve($link, $renderContext, new Messages());
        $path = parse_url($link->getUrl(), PHP_URL_PATH);
        $host = parse_url($link->getUrl(), PHP_URL_HOST);
        // …/<version>/<locale>/, as every manual on docs.typo3.org is served
        if (!is_string($path) || !is_string($host) || preg_match('#^(.*/([^/]+)/[a-z]{2}-[a-z]{2}/)#', $path, $matches) !== 1) {
            return null;
        }

        return [
            'version' => $matches[2],
            'base' => 'https://' . $host . $matches[1],
        ];
    }

    /** The interlink shortcode a link names, without its version. */
    private function shortcode(AbstractLinkInlineNode $link): string
    {
        if (!$link instanceof CrossReferenceNode || $link->getInterlinkDomain() === '') {
            return '';
        }
        $parts = $this->interlinkParser->parse($link->getInterlinkDomain());
        if ($parts === null) {
            return '';
        }

        return $parts->kind === 'default' ? $parts->package : $parts->vendor . '/' . $parts->package;
    }

    /** The first link of a menu entry, outside the list of its children. */
    private function link(ListItemNode $item): ?AbstractLinkInlineNode
    {
        foreach ($item->getChildren() as $child) {
            if ($child instanceof ListNode) {
                continue;
            }
            $link = $this->firstLink($child);
            if ($link !== null) {
                return $link;
            }
        }

        return null;
    }

    private function firstLink(Node $node): ?AbstractLinkInlineNode
    {
        if ($node instanceof AbstractLinkInlineNode) {
            return $node;
        }
        if (!$node instanceof CompoundNode) {
            return null;
        }
        foreach ($node->getChildren() as $child) {
            $link = $child instanceof Node ? $this->firstLink($child) : null;
            if ($link !== null) {
                return $link;
            }
        }

        return null;
    }
}
