<?php

declare(strict_types=1);

namespace T3Docs\Typo3DocsTheme\Directives;

use phpDocumentor\Guides\Nodes\CollectionNode;
use phpDocumentor\Guides\Nodes\Inline\PlainTextInlineNode;
use phpDocumentor\Guides\Nodes\Inline\ReferenceNode;
use phpDocumentor\Guides\Nodes\Node;
use phpDocumentor\Guides\RestructuredText\Directives\SubDirective;
use phpDocumentor\Guides\RestructuredText\Parser\BlockContext;
use phpDocumentor\Guides\RestructuredText\Parser\Directive;
use phpDocumentor\Guides\RestructuredText\Parser\Productions\Rule;
use Psr\Log\LoggerInterface;
use T3Docs\Typo3DocsTheme\Nodes\Typo3VersionChangeNode;
use T3Docs\Typo3DocsTheme\Settings\Typo3DocsThemeSettings;

use function array_values;
use function explode;
use function str_contains;
use function str_starts_with;
use function substr;
use function trim;

/**
 * TYPO3 specific version of phpDocumentor's version change directives
 * (versionadded, versionchanged, deprecated).
 *
 * All three directives support a ":changelog:" option that renders a link to
 * the related changelog entry. The value is resolved against the changelog
 * inventory (interlink), so an entry that does not exist produces a warning and
 * no link instead of a link that 404s when clicked.
 *
 * For a TYPO3 core change, pass the changelog entry identifier; it is resolved
 * against the "changelog" inventory:
 *
 * ..  versionchanged:: 14.0
 *     :changelog: feature-107628-1729026000
 *
 *     This module has been moved from :guilabel:`System` to
 *     :guilabel:`Administration`.
 *
 * For an extension change, pass the extension's interlink shortcode
 * ("vendor/package") followed by the changelog entry anchor. It is resolved
 * against that extension's inventory:
 *
 * ..  versionchanged:: 2.0
 *     :changelog: acme/acme-blog:changes-2-0-0
 *
 *     The teaser field was renamed; see the changelog entry for the migration.
 *
 * When linking the changelog of the current manual itself, use the short
 * "#anchor" form. It resolves against this manual's own labels and requires
 * "interlink-shortcode" (from guides.xml) to be set (no link is rendered, and a
 * warning is logged, if that setting is missing):
 *
 * ..  versionchanged:: 2.0
 *     :changelog: #changes-2-0-0
 *
 *     A configuration option was renamed; see the changelog for the migration.
 */
abstract class AbstractTypo3VersionChangeDirective extends SubDirective
{
    private const CHANGELOG_INVENTORY = 'changelog';
    private const CHANGELOG_LINK_TEXT = 'See changelog entry';

    /** @param Rule<CollectionNode> $startingRule */
    public function __construct(
        Rule $startingRule,
        private readonly string $type,
        private readonly string $label,
        private readonly Typo3DocsThemeSettings $themeSettings,
        private readonly LoggerInterface $logger,
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
            $this->buildChangelogReference($directive->getOptionString('changelog'), $blockContext),
        );
    }

    /**
     * Turn the ":changelog:" option into an interlink reference that resolves
     * against the changelog inventory during rendering. Returns null when the
     * option is empty or malformed (a warning is logged in the latter case).
     */
    private function buildChangelogReference(string $changelog, BlockContext $blockContext): ReferenceNode|null
    {
        $changelog = trim($changelog);
        if ($changelog === '') {
            return null;
        }

        $ownShortcode = $this->themeSettings->getSettings('interlink_shortcode');

        if (str_starts_with($changelog, '#')) {
            // "#anchor": the changelog of the current manual itself. Emit a local
            // reference (empty interlink domain) so it resolves against this
            // manual's own labels. The "interlink-shortcode" setting must be
            // present so the intent (a self-reference) is explicit and matches
            // the other forms; without it, warn and render no link.
            if ($ownShortcode === '') {
                $this->logger->warning(
                    'The ":changelog: #..." form requires "interlink-shortcode" to be set in the guides.xml. ',
                    $blockContext->getLoggerInformation(),
                );

                return null;
            }

            $interlinkDomain = '';
            $anchor = trim(substr($changelog, 1));
        } elseif (str_contains($changelog, ':')) {
            // Explicit "<shortcode>:<anchor>", e.g. "vendor/package:anchor".
            $parts = explode(':', $changelog, 2);
            $interlinkDomain = trim($parts[0]);
            $anchor = trim($parts[1] ?? '');

            if ($interlinkDomain === '') {
                $this->logger->warning(
                    'The ":changelog:" option is malformed (no shortcode before the colon). ',
                    $blockContext->getLoggerInformation(),
                );

                return null;
            }

            // A reference to the manual's own shortcode is a self-reference;
            // emit it as a local reference, like the "#anchor" form.
            if ($interlinkDomain === $ownShortcode) {
                $interlinkDomain = '';
            }
        } else {
            // Bare value: a TYPO3 core changelog entry identifier.
            $interlinkDomain = self::CHANGELOG_INVENTORY;
            $anchor = $changelog;
        }

        if ($anchor === '') {
            $this->logger->warning(
                'The ":changelog:" option has an empty changelog entry anchor. ',
                $blockContext->getLoggerInformation(),
            );

            return null;
        }

        return new ReferenceNode(
            $anchor,
            [new PlainTextInlineNode(self::CHANGELOG_LINK_TEXT)],
            $interlinkDomain,
        );
    }
}
