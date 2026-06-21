<?php

declare(strict_types=1);

namespace T3Docs\Typo3DocsTheme\Directives;

use phpDocumentor\Guides\Nodes\CollectionNode;
use phpDocumentor\Guides\Nodes\Node;
use phpDocumentor\Guides\RestructuredText\Directives\SubDirective;
use phpDocumentor\Guides\RestructuredText\Parser\BlockContext;
use phpDocumentor\Guides\RestructuredText\Parser\Directive;
use phpDocumentor\Guides\RestructuredText\Parser\Productions\Rule;
use T3Docs\Typo3DocsTheme\Nodes\Typo3VersionChangeNode;
use T3Docs\Typo3DocsTheme\Settings\Typo3DocsThemeSettings;

use function array_values;
use function explode;
use function str_contains;
use function str_replace;
use function str_starts_with;
use function substr;
use function trim;

/**
 * TYPO3 specific version of phpDocumentor's version change directives
 * (versionadded, versionchanged, deprecated).
 *
 * In addition to the default behaviour they support a ":changelog:" option that
 * renders a link to the related changelog entry via the docs.typo3.org
 * permalink service.
 *
 * For a TYPO3 core change, pass the changelog entry identifier (resolved against
 * the "changelog" inventory):
 *
 * ..  versionchanged:: 14.0
 *     :changelog: feature-107628-1729026000
 *
 *     This module has been moved from :guilabel:`System` to
 *     :guilabel:`Administration`.
 *
 * For an extension change, pass the extension's permalink shortcode followed by
 * the changelog entry anchor. The shortcode is normalized to the dash form the
 * permalink service expects ("vendor/package" -> "vendor-package"), just like
 * the "copy permalink" button on docs.typo3.org:
 *
 * ..  versionchanged:: 2.0
 *     :changelog: acme/acme-blog:changes-2-0-0
 *
 *     The teaser field was renamed; see the changelog entry for the migration.
 *
 * When linking the changelog of the current manual itself, use the short
 * "#anchor" form. The manual's own "interlink-shortcode" (from guides.xml) is
 * used automatically, so it does not have to be repeated:
 *
 * ..  versionchanged:: 2.0
 *     :changelog: #changes-2-0-0
 *
 *     ...
 */
abstract class AbstractTypo3VersionChangeDirective extends SubDirective
{
    private const PERMALINK_BASE = 'https://docs.typo3.org/permalink/';
    private const CHANGELOG_INVENTORY = 'changelog';

    /** @param Rule<CollectionNode> $startingRule */
    public function __construct(
        Rule $startingRule,
        private readonly string $type,
        private readonly string $label,
        private readonly Typo3DocsThemeSettings $themeSettings,
    ) {
        parent::__construct($startingRule);
    }

    final public function getName(): string
    {
        return $this->type;
    }

    final protected function processSub(
        BlockContext $blockContext,
        CollectionNode $collectionNode,
        Directive $directive,
    ): Node {
        return new Typo3VersionChangeNode(
            $this->type,
            $this->label,
            $directive->getData(),
            array_values($collectionNode->getChildren()),
            $this->buildChangelogUrl($directive->getOptionString('changelog')),
        );
    }

    private function buildChangelogUrl(string $changelog): string|null
    {
        $changelog = trim($changelog);
        if ($changelog === '') {
            return null;
        }

        if (str_starts_with($changelog, '#')) {
            // "#anchor": the changelog of the current manual itself. Use its own
            // interlink-shortcode (from guides.xml) so it need not be repeated.
            $shortcode = $this->themeSettings->getSettings('interlink_shortcode');
            if ($shortcode === '') {
                return null;
            }

            $target = str_replace('/', '-', $shortcode) . ':' . substr($changelog, 1);
        } elseif (str_contains($changelog, ':')) {
            // Explicit "<shortcode>:<anchor>". Normalize the shortcode to the
            // dash form the permalink service expects (vendor/package ->
            // vendor-package), exactly like the "copy permalink" button.
            $parts = explode(':', $changelog, 2);
            $target = str_replace('/', '-', $parts[0]) . ':' . ($parts[1] ?? '');
        } else {
            // Bare value: a TYPO3 core changelog entry identifier.
            $target = self::CHANGELOG_INVENTORY . ':' . $changelog;
        }

        return self::PERMALINK_BASE . $target;
    }
}
