<?php

declare(strict_types=1);

namespace T3Docs\Typo3DocsTheme\Directives;

use phpDocumentor\Guides\Nodes\CollectionNode;
use phpDocumentor\Guides\Nodes\Inline\ReferenceNode;
use phpDocumentor\Guides\Nodes\Node;
use phpDocumentor\Guides\RestructuredText\Directives\SubDirective;
use phpDocumentor\Guides\RestructuredText\Parser\BlockContext;
use phpDocumentor\Guides\RestructuredText\Parser\Directive;
use phpDocumentor\Guides\RestructuredText\Parser\Productions\Rule;
use phpDocumentor\Guides\RestructuredText\Parser\References\EmbeddedReferenceParser;
use T3Docs\Typo3DocsTheme\Changelog\ChangelogReferences;
use T3Docs\Typo3DocsTheme\Nodes\Typo3VersionChangeNode;

use function array_values;

/**
 * TYPO3 specific version of phpDocumentor's version change directives
 * (versionadded, versionchanged, deprecated).
 *
 * All three directives support a ":changelog:" option that renders a link to
 * the related changelog entry. The value is resolved as a cross-reference -
 * against the core changelog inventory, against another manual's inventory, or
 * against this manual's own labels, depending on the form - so a target that
 * does not exist produces a warning and the theme's unresolved-reference
 * marker, not a link that 404s when clicked.
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
 * "#anchor" form. It resolves against this manual's own labels:
 *
 * ..  versionchanged:: 2.0
 *     :changelog: #changes-2-0-0
 *
 *     A configuration option was renamed; see the changelog for the migration.
 *
 * The link text is the title of the entry the reference resolves to, so each
 * link says which change it leads to. Where that title does not describe the
 * change - an extension whose whole changelog carries one label, say - give the
 * text explicitly, in the form every other reference in the project uses:
 *
 * ..  versionchanged:: 2.0
 *     :changelog: Renaming the teaser field <acme/acme-blog:changelog>
 *
 *     The teaser field was renamed; see the changelog entry for the migration.
 */
abstract class AbstractTypo3VersionChangeDirective extends SubDirective
{
    use EmbeddedReferenceParser;

    public const CHANGELOG_LINK_CLASS = 'versionchange-changelog';

    /** @param Rule<CollectionNode> $startingRule */
    public function __construct(
        Rule $startingRule,
        private readonly string $type,
        private readonly string $label,
        private readonly ChangelogReferences $changelogReferences,
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
            $this->buildChangelogReference($directive, $blockContext),
        );
    }

    /**
     * Turn the ":changelog:" option into a reference that is resolved during
     * rendering: against the core changelog inventory, against another manual's
     * inventory, or against this manual's own labels. Returns null when the
     * option is absent, valueless or malformed (a warning is logged in the
     * latter two cases).
     */
    private function buildChangelogReference(Directive $directive, BlockContext $blockContext): ReferenceNode|null
    {
        if (!$directive->hasOption('changelog')) {
            return null;
        }

        // ":changelog:" with nothing behind it is parsed as the flag "true", which
        // strval()s to "1" and would otherwise be taken for a changelog entry id.
        $value = $directive->getOption('changelog')->getValue();
        $changelog = $value === true ? '' : (string) $value;

        return $this->changelogReferences->build($changelog, 'changelog', $blockContext->getLoggerInformation());
    }
}
